import {FunctionField, Show, SimpleShowLayout, TextField, TopToolbar, useGetList, useShowContext} from 'react-admin';
import {Box, CircularProgress, Tooltip, Typography} from '@mui/material';
import Timeline from '@mui/lab/Timeline';
import TimelineItem from '@mui/lab/TimelineItem';
import TimelineSeparator from '@mui/lab/TimelineSeparator';
import TimelineConnector from '@mui/lab/TimelineConnector';
import TimelineContent from '@mui/lab/TimelineContent';
import TimelineDot from '@mui/lab/TimelineDot';
import TimelineOppositeContent from '@mui/lab/TimelineOppositeContent';
import {formatDistanceToNow} from "date-fns";
import {ru} from "date-fns/locale";
import React from "react";
import {StatusChip} from './common';
import {OrderInfo} from './OrderInfo';

const CommentsTimeline = ({ticketId, statusColors}: {
  ticketId: string, statusColors: Record<string, string>
}) => {
  const {data: comments, isLoading, error} = useGetList(
    'support_ticket_comments',
    {
      filter: {'supportTicket': ticketId},
      sort: {field: 'createdAt', order: 'DESC'},
      pagination: {page: 1, perPage: 100}
    }
  );

  if (isLoading) {
    return <CircularProgress/>;
  }

  if (error) {
    return <Typography variant="body2" color="error">Ошибка загрузки активности</Typography>;
  }

  if (!comments || comments.length === 0) {
    return /*(
      <Box>
        <Typography variant="body2" color="textSecondary">Пока нет активности</Typography>
      </Box>
    )*/;
  }

  return (
    <Box>
      <Timeline position="right">
        {comments.map((comment: any, index: number) => {
          const statusColor = statusColors[comment.status] || 'primary';
          return (
            <TimelineItem key={comment.id || index}>
              <TimelineOppositeContent sx={{m: 'auto 0'}}>
                <Typography
                  variant="body2"
                  sx={{
                    color: `${statusColor}.main`,
                    fontWeight: 'bold'
                  }}
                >
                  {comment.statusDisplayName}
                </Typography>
                <Box sx={{mt: 1}}>
                  {comment.closingReason && (
                    <Typography variant="body2" color="secondary">
                      {comment.closingReasonDisplayName}
                    </Typography>
                  )}
                </Box>
              </TimelineOppositeContent>
              <TimelineSeparator>
                <TimelineConnector/>
                <TimelineDot/>
                <TimelineConnector/>
              </TimelineSeparator>
              <TimelineContent sx={{py: '12px', px: 2}}>
                <Box sx={{mt: 1}}>
                  <Typography variant="body2" color="secondary">
                    <Tooltip title={new Date(comment.createdAt).toLocaleString('ru-RU')}>
                      <span>{formatDistanceToNow(new Date(comment.createdAt), {addSuffix: true, locale: ru})}</span>
                    </Tooltip>
                  </Typography>
                </Box>
                <Typography variant="body1" sx={{mt: 1}}>
                  {comment.comment}
                </Typography>
              </TimelineContent>
            </TimelineItem>
          );
        })}
      </Timeline>
    </Box>
  );
};

const SupportTicketActions = () => {
  return <TopToolbar/>;
};

const SupportTicketTitle = () => {
  const {record} = useShowContext();
  return <span>Заявка {record?.subject || 'Заявка'}</span>;
};

const SupportTicketShowContent = () => {
  const [, setClosingReasonChoices] = React.useState<any[]>([]);
  const [statusColors, setStatusColors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    const loadData = async () => {
      try {
        const [reasonsResponse, statusesResponse] = await Promise.all([
          fetch('/api/support-tickets/closing-reasons'),
          fetch('/api/support-tickets/statuses')
        ]);
        const reasons = await reasonsResponse.json();
        const statuses = await statusesResponse.json();
        setClosingReasonChoices(reasons);
        const colorsMap = statuses.reduce((acc: Record<string, string>, status: any) => {
          acc[status.id] = status.color;
          return acc;
        }, {});
        setStatusColors(colorsMap);
      } catch (error) {
        console.error('Failed to load data:', error);
      }
    };
    loadData();
  }, []);

  return (
    <SimpleShowLayout>
      <TextField source="subject" label="Причина обращения"/>
      <TextField source="description" label="Цель обращения"/>
      <TextField source="authorName" label="Автор заявки"/>
      <FunctionField
        label="Создана"
        render={(record: any) => (
          <Tooltip title={new Date(record.createdAt).toLocaleString('ru-RU')}>
            <span>{formatDistanceToNow(new Date(record.createdAt), {addSuffix: true, locale: ru})}</span>
          </Tooltip>
        )}
      />
      <FunctionField
        label="Статус"
        render={(record: any) => <StatusChip status={record?.currentStatus} statusValue={record?.currentStatusValue}
                                             color={statusColors[record?.currentStatusValue]}/>}
      />
      <FunctionField
        label="Информация о заказе"
        render={(record: any) => record?.orderId &&
          <OrderInfo orderId={record.orderId} orderData={record.orderData}/>}
      />
      <FunctionField
        label="Активность"
        render={(record: any) => record?.id &&
          <CommentsTimeline ticketId={record.id} statusColors={statusColors}/>}
      />
    </SimpleShowLayout>
  );
};

export const SupportTicketShow = () => (
  <Show title={<SupportTicketTitle/>} actions={<SupportTicketActions/>}>
    <SupportTicketShowContent/>
  </Show>
);
