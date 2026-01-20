import {FunctionField, Show, SimpleShowLayout, TextField, TopToolbar, useGetList, useShowContext} from 'react-admin';
import {Box, CircularProgress, Tooltip, Typography, Select, MenuItem, FormControl, Button} from '@mui/material';
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
import {OrderInfo} from './OrderInfo';
import {StatusChangeForm} from './StatusChangeForm';
import {MediaUpload} from './MediaUpload';

const CommentsTimeline = ({ticketId, statusColors, refetchKey}: {
  ticketId: string,
  statusColors: Record<string, string>,
  refetchKey?: number
}) => {
  const {data: comments, isLoading, error, refetch} = useGetList(
    'support_ticket_comments',
    {
      filter: {'supportTicket': ticketId},
      sort: {field: 'createdAt', order: 'DESC'},
      pagination: {page: 1, perPage: 100}
    }
  );

  React.useEffect(() => {
    if (refetchKey && refetchKey > 0) {
      refetch();
    }
  }, [refetchKey, refetch]);

  if (isLoading) {
    return <CircularProgress/>;
  }

  if (error) {
    return <Typography variant="body2" color="error">Ошибка загрузки активности</Typography>;
  }

  if (!comments || comments.length === 0) {
    return null;
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
  const [commentsRefetchKey, setCommentsRefetchKey] = React.useState(0);
  const [users, setUsers] = React.useState<any[]>([]);
  const [isEditingUser, setIsEditingUser] = React.useState(false);
  const [selectedUserId, setSelectedUserId] = React.useState<number | null>(null);
  const [assigning, setAssigning] = React.useState(false);
  const {record, refetch} = useShowContext();
  const selectRef = React.useRef<HTMLDivElement>(null);

  React.useEffect(() => {
    const loadData = async () => {
      try {
        const [reasonsResponse, statusesResponse, usersResponse] = await Promise.all([
          fetch('/api/support-tickets/closing-reasons'),
          fetch('/api/support-tickets/statuses'),
          fetch('/api/support-tickets/assignable-users')
        ]);
        const reasons = await reasonsResponse.json();
        const statuses = await statusesResponse.json();
        const usersData = await usersResponse.json();
        setClosingReasonChoices(reasons);
        const colorsMap = statuses.reduce((acc: Record<string, string>, status: any) => {
          acc[status.id] = status.color;
          return acc;
        }, {});
        setStatusColors(colorsMap);
        setUsers(usersData);
      } catch (error) {
        console.error('Failed to load data:', error);
      }
    };
    loadData();
  }, []);

  const handleUserSelect = async (userId: number) => {
    if (!record?.id) return;

    setAssigning(true);
    try {
      const response = await fetch(`/api/support-tickets/${record.id.split('/').pop()}/assign-user`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({userId}),
      });

      if (response.ok) {
        refetch();
        setIsEditingUser(false);
        setSelectedUserId(null);
      } else {
        console.error('Failed to assign user');
      }
    } catch (error) {
      console.error('Error assigning user:', error);
    } finally {
      setAssigning(false);
    }
  };


  return (
    <SimpleShowLayout>
      <TextField source="subject" label="Причина обращения"/>
      <TextField source="description" label="Цель обращения"/>
      <TextField source="authorName" label="Автор заявки"/>
      <FunctionField
        label="Ответственный"
        render={(record: any) => (
          isEditingUser ? (
            <Box ref={selectRef} sx={{ maxWidth: 250 }}>
              <FormControl size="small" fullWidth>
                <Select
                  value={selectedUserId || ''}
                  onChange={(e) => {
                    const userId = Number(e.target.value);
                    if (userId) {
                      handleUserSelect(userId);
                    }
                  }}
                  onBlur={() => {
                    setIsEditingUser(false);
                    setSelectedUserId(null);
                  }}
                  displayEmpty
                  disabled={assigning}
                  MenuProps={{
                    PaperProps: {
                      style: {
                        maxHeight: 200,
                      },
                    },
                  }}
                >
                  <MenuItem value="">
                    <em>Выберите ответственного</em>
                  </MenuItem>
                  {users.map((user) => (
                    <MenuItem key={user.id} value={user.id}>
                      {user.name}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Box>
          ) : record?.currentStatusValue === 'completed' ? (
            <Typography component="span">
              {record?.userName || 'Не назначен'}
            </Typography>
          ) : (
            <Tooltip title="Нажмите чтобы сменить ответственного">
              <Typography
                component="span"
                sx={{
                  cursor: 'pointer',
                  textDecoration: 'underline',
                  textDecorationStyle: 'dashed',
                  color: 'primary.main',
                  display: 'inline'
                }}
                onClick={() => setIsEditingUser(true)}
              >
                {record?.userName || 'Не назначен'}
              </Typography>
            </Tooltip>
          )
        )}
      />
      <FunctionField
        label="Создана"
        render={(record: any) => (
          <Tooltip title={new Date(record.createdAt).toLocaleString('ru-RU')}>
            <span>{formatDistanceToNow(new Date(record.createdAt), {addSuffix: true, locale: ru})}</span>
          </Tooltip>
        )}
      />
      {/*<FunctionField
        label="Статус"
        render={(record: any) => <StatusChip status={record?.currentStatus} statusValue={record?.currentStatusValue}
                                             color={statusColors[record?.currentStatusValue]}/>}
      />*/}
      <FunctionField
        label="Информация о заказе"
        render={(record: any) => record?.orderId &&
          <OrderInfo orderId={record.orderId} orderData={record.orderData}/>}
      />
      <FunctionField
        label=""
        render={() => <StatusChangeForm onStatusChanged={() => setCommentsRefetchKey(prev => prev + 1)}/>}
      />
      <FunctionField
        label=""
        render={(record: any) => record?.id && <MediaUpload ticketId={record.id.split('/').pop() || ''} />}
      />
      <FunctionField
        label="Активность"
        render={(record: any) => record?.id &&
          <CommentsTimeline
            ticketId={record.id}
            statusColors={statusColors}
            refetchKey={commentsRefetchKey}
          />}
      />
    </SimpleShowLayout>
  );
};

export const SupportTicketShow = () => (
  <Show title={<SupportTicketTitle/>} actions={<SupportTicketActions/>}>
    <SupportTicketShowContent/>
  </Show>
);
