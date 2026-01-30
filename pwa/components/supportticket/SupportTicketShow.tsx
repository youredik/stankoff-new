import {FunctionField, Show, SimpleShowLayout, TextField, TopToolbar, useGetList, useNotify, useShowContext} from 'react-admin';
import {Autocomplete, Box, Button, Card, CardContent, CircularProgress, Divider, Popover, Tab, Tabs, TextField as MuiTextField, Tooltip, Typography} from '@mui/material';
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
  const [assignAnchorEl, setAssignAnchorEl] = React.useState<HTMLElement | null>(null);
  const [selectedUserId, setSelectedUserId] = React.useState<number | null>(null);
  const [assigning, setAssigning] = React.useState(false);
  const [activeTab, setActiveTab] = React.useState(0);
  const {record, refetch} = useShowContext();
  const notify = useNotify();

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
      setSelectedUserId(null);
      setAssignAnchorEl(null);
    } catch (err: any) {
      notify(err.message, {type: 'error'});
    } finally {
      setAssigning(false);
    }
  };

  const handleAssignOpen = (event: React.MouseEvent<HTMLElement>) => {
    if (record?.status === 'completed') {
      return;
    }
    setAssignAnchorEl(event.currentTarget);
    setSelectedUserId(null);
  };

  const handleAssignClose = () => {
    if (assigning) {
      return;
    }
    setAssignAnchorEl(null);
    setSelectedUserId(null);
  };

  const handleAssignConfirm = async () => {
    if (!selectedUserId) {
      return;
    }
    await handleUserSelect(selectedUserId);
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
              <>
                {record?.status === 'completed' ? (
                  <Typography component="span">
                    {record?.userName || 'Не назначен'}
                  </Typography>
                ) : (
                  <Tooltip title="Нажмите чтобы сменить ответственного">
                    <Button
                      variant="text"
                      size="small"
                      onClick={handleAssignOpen}
                      sx={{
                        textTransform: 'none',
                        px: 0,
                        minWidth: 0,
                        textDecoration: 'underline',
                        textDecorationStyle: 'dashed',
                      }}
                    >
                      {record?.userName || 'Не назначен'}
                    </Button>
                  </Tooltip>
                )}
                <Popover
                  open={Boolean(assignAnchorEl)}
                  anchorEl={assignAnchorEl}
                  onClose={handleAssignClose}
                  anchorOrigin={{vertical: 'bottom', horizontal: 'left'}}
                  transformOrigin={{vertical: 'top', horizontal: 'left'}}
                >
                  <Box sx={{p: 2, width: 320}}>
                    <Typography variant="subtitle2" sx={{mb: 1}}>
                      Сменить ответственного
                    </Typography>
                    <Autocomplete
                      options={users}
                      getOptionLabel={(option) => option?.name || ''}
                      value={users.find((user) => user.id === selectedUserId) || null}
                      onChange={(_, value) => setSelectedUserId(value?.id ?? null)}
                      renderInput={(params) => (
                        <MuiTextField
                          {...params}
                          size="small"
                          placeholder="Выберите сотрудника"
                        />
                      )}
                      isOptionEqualToValue={(option, value) => option.id === value.id}
                      disabled={assigning}
                    />
                    <Box sx={{display: 'flex', justifyContent: 'flex-end', gap: 1, mt: 2}}>
                      <Button size="small" onClick={handleAssignClose} disabled={assigning}>
                        Отмена
                      </Button>
                      <Button
                        size="small"
                        variant="contained"
                        onClick={handleAssignConfirm}
                        disabled={!selectedUserId || assigning}
                      >
                        {assigning ? 'Назначение...' : 'Назначить'}
                      </Button>
                    </Box>
                  </Box>
                </Popover>
              </>
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

      {/* Правая колонка: активности */}
      <Box sx={{ flex: 1 }}>
        <SimpleShowLayout>
          <Tabs value={activeTab} onChange={(_, value) => setActiveTab(value)}>
            <Tab label="Активность" />
            <Tab label="Мессенджер" />
            <Tab label="Телефония" />
          </Tabs>
          {activeTab === 0 && (
            <>
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
            </>
          )}
          {activeTab === 1 && (
            <Box sx={{ pt: 2 }}>
              <Typography color="text.secondary">На стадии разработки</Typography>
            </Box>
          )}
          {activeTab === 2 && (
            <Box sx={{ pt: 2 }}>
              <Typography color="text.secondary">На стадии разработки</Typography>
            </Box>
          )}
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
