import {FunctionField, Show, SimpleShowLayout, TextField, TopToolbar, useGetList, useNotify, useShowContext} from 'react-admin';
import {Box, CircularProgress, Tooltip, Typography, Select, MenuItem, FormControl, Button, Card, CardContent, Divider} from '@mui/material';
import {formatDistanceToNow} from "date-fns";
import {ru} from "date-fns/locale";
import React from "react";
import {OrderInfo} from './OrderInfo';
import {StatusChangeForm} from './StatusChangeForm';
import {MediaUpload} from './MediaUpload';
import {StatusChip} from "./common";
import {assignUser} from '../../services/supportTicketService';

const CommentsList = ({ticketId, statusColors, refetchKey}: {
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
      {comments.map((comment: any, index: number) => {
        const statusColor = statusColors[comment.status] || 'primary';
        return (
          <Card key={comment.id || index} sx={{ mb: 2 }}>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, mb: 1 }}>
                <Typography variant="body2" sx={{ color: `${statusColor}.main`, fontWeight: 'bold' }}>
                  {comment.statusDisplayName}
                </Typography>
                {comment.userName && (
                  <Typography variant="body2" color="primary">
                    {comment.userName}
                  </Typography>
                )}
                <Typography variant="body2" color="secondary">
                  <Tooltip title={new Date(comment.createdAt).toLocaleString('ru-RU')}>
                    <span>{formatDistanceToNow(new Date(comment.createdAt), {addSuffix: true, locale: ru})}</span>
                  </Tooltip>
                </Typography>
              </Box>
              {comment.closingReason && (
                <Typography variant="body2" color="secondary" sx={{ mb: 1 }}>
                  Причина закрытия: {comment.closingReasonDisplayName}
                </Typography>
              )}
              <Typography variant="body1">
                {comment.comment}
              </Typography>
            </CardContent>
          </Card>
        );
      })}
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
  const [openSelect, setOpenSelect] = React.useState(false);
  const [selectedUserId, setSelectedUserId] = React.useState<number | null>(null);
  const [assigning, setAssigning] = React.useState(false);
  const {record, refetch} = useShowContext();
  const notify = useNotify();
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
      const ticketId = record.id.split('/').pop();
      await assignUser(ticketId, userId);

      notify('Ответственный успешно изменен', {type: 'success'});
      refetch();
      setIsEditingUser(false);
      setSelectedUserId(null);
      setOpenSelect(false);
    } catch (err: any) {
      notify(err.message, {type: 'error'});
    } finally {
      setAssigning(false);
    }
  };


  return (
    <Box sx={{ display: 'flex', gap: 4 }}>
      {/* Левая колонка: информация о заявке */}
      <Box sx={{ flex: 1 }}>
        <SimpleShowLayout>
          <FunctionField
            label="Номер заявки"
            render={(record: any) => record?.id ? record.id.split('/').pop() : ''}
          />
          <TextField source="subject" label="Причина обращения"/>
          <TextField source="description" label="Цель обращения"/>
          <FunctionField
            label="Статус"
            render={(record: any) => <StatusChip status={record?.currentStatus} statusValue={record?.status}
                                                 color={statusColors[record?.status]}/>}
          />
          <TextField source="authorName" label="Автор заявки"/>
          <FunctionField
            label="Ответственный"
            render={(record: any) => (
              isEditingUser ? (
                <Box ref={selectRef} sx={{ maxWidth: 250 }}>
                  <FormControl size="small" fullWidth>
                    <Select
                      open={openSelect}
                      onClose={() => setOpenSelect(false)}
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
                        setOpenSelect(false);
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
              ) : record?.status === 'completed' ? (
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
                    onClick={() => {
                      setIsEditingUser(true);
                      setOpenSelect(true);
                    }}
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
          {record?.closedAt && (
            <FunctionField
              label="Завершено"
              render={(record: any) => (
                <Tooltip title={new Date(record.closedAt).toLocaleString('ru-RU')}>
                  <span>{formatDistanceToNow(new Date(record.closedAt), {addSuffix: true, locale: ru})}</span>
                </Tooltip>
              )}
            />
          )}
          <FunctionField
            label="Информация о заказе"
            render={(record: any) => record?.orderId &&
              <OrderInfo orderId={record.orderId} orderData={record.orderData}/>}
          />
          <FunctionField
            label=""
            render={(record: any) => record?.id && <MediaUpload ticketId={record.id.split('/').pop() || ''} />}
          />
        </SimpleShowLayout>
      </Box>

      {/* Разделитель */}
      <Divider orientation="vertical" flexItem />

      {/* Правая колонка: форма статуса и комментарии */}
      <Box sx={{ flex: 1 }}>
        <SimpleShowLayout>
          <FunctionField
            label=""
            render={() => <StatusChangeForm onStatusChanged={() => setCommentsRefetchKey(prev => prev + 1)}/>}
          />
          <FunctionField
            label="Активность"
            render={(record: any) => record?.id &&
              <CommentsList
                ticketId={record.id}
                statusColors={statusColors}
                refetchKey={commentsRefetchKey}
              />}
          />
        </SimpleShowLayout>
      </Box>
    </Box>
  );
};

export const SupportTicketShow = () => (
  <Show title={<SupportTicketTitle/>} actions={<SupportTicketActions/>}>
    <SupportTicketShowContent/>
  </Show>
);
