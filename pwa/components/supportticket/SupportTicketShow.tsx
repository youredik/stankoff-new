import {
  DateField,
  FunctionField,
  Show,
  SimpleShowLayout,
  TextField,
  TopToolbar,
  useDataProvider,
  useGetList,
  useNotify,
  useRefresh,
  useShowContext
} from 'react-admin';
import { Box, Button, Card, CircularProgress, MenuItem, Select, Tooltip, Typography, Link } from '@mui/material';
import Timeline from '@mui/lab/Timeline';
import TimelineItem from '@mui/lab/TimelineItem';
import TimelineSeparator from '@mui/lab/TimelineSeparator';
import TimelineConnector from '@mui/lab/TimelineConnector';
import TimelineContent from '@mui/lab/TimelineContent';
import TimelineDot from '@mui/lab/TimelineDot';
import TimelineOppositeContent from '@mui/lab/TimelineOppositeContent';
import { formatDistanceToNow } from "date-fns";
import { ru } from "date-fns/locale";
import PersonAddIcon from "@mui/icons-material/PersonAdd";
import React from "react";
import { getSession } from 'next-auth/react';
import { getStatusColor, StatusChip } from './common';
import { OrderInfo } from './OrderInfo';

const TicketActions = ({ ticketId, processInstanceKey, currentStatus, closingReasonChoices }: {
  ticketId: string,
  processInstanceKey?: string,
  currentStatus: string,
  closingReasonChoices: any[]
}) => {
  const dataProvider = useDataProvider();
  const notify = useNotify();
  const refresh = useRefresh();
  const [actionLoading, setActionLoading] = React.useState<string | null>(null);
  const [showCommentForm, setShowCommentForm] = React.useState<string | null>(null);
  const [comment, setComment] = React.useState('');
  const [closingReason, setClosingReason] = React.useState('');
  const [selectedAction, setSelectedAction] = React.useState('');

  const handleAction = async (action: string, activityId?: string, status?: string, closingReasonValue?: string) => {
    setActionLoading(action);

    try {
      const commentData = comment.trim() || (action === 'take_in_work' || action === 'change_to_in_progress' ? '' : comment);

      if (action !== 'take_in_work' && action !== 'assign' && action !== 'change_to_in_progress' && !commentData) {
        notify('Комментарий обязателен', { type: 'error' });
        return;
      }

      if (action === 'complete' && !closingReason) {
        notify('Причина закрытия обязательна', { type: 'error' });
        return;
      }

      // Special handling for postpone and complete actions - use dedicated endpoints
      if (action === 'postpone') {
        await dataProvider.create('support_tickets_postpone', {
          data: {
            comment: commentData,
            activityId: activityId,
            supportTicket: ticketId,
          }
        });
      } else if (action === 'complete') {
        await dataProvider.create('support_tickets_complete', {
          data: {
            comment: commentData,
            activityId: activityId,
            closingReason: closingReasonValue,
            supportTicket: ticketId,
          }
        });
      } else {
        // Create comment for other actions
        await dataProvider.create('support_ticket_comments', {
          data: {
            comment: commentData,
            status: status || currentStatus,
            closingReason: closingReasonValue || null,
            supportTicket: ticketId,
          }
        });
      }

      notify('Действие выполнено успешно', { type: 'success' });
      setComment('');
      setClosingReason('');
      setShowCommentForm(null);
      refresh();
    } catch (error) {
      notify('Ошибка при выполнении действия', { type: 'error' });
    } finally {
      setActionLoading(null);
    }
  };

  const getActionOptions = () => {
    switch (currentStatus) {
      case 'new':
        return [
          { id: 'take_in_work', name: 'Взять в работу' }
        ];
      case 'postponed':
        return [
          { id: 'change_to_in_progress', name: 'Сменить статус в работе' }
        ];
      case 'in_progress':
        return [
          { id: 'postpone', name: 'Отложить' },
          { id: 'complete', name: 'Завершить' },
          { id: 'add_comment', name: 'Добавить комментарий' }
        ];
      default:
        return [];
    }
  };

  const handleActionSelect = (actionId: string) => {
    // Reset forms first
    setShowCommentForm(null);

    switch (actionId) {
      case 'take_in_work':
        setSelectedAction('');
        handleTakeInWork();
        break;
      case 'change_to_in_progress':
        setSelectedAction('');
        handleChangeToInProgress();
        break;
      case 'assign':
        setSelectedAction('');
        handleAction('assign', 'Activity_1ht60dz', 'in_progress');
        break;
      case 'postpone':
        setSelectedAction(actionId);
        setShowCommentForm('postpone');
        break;
      case 'complete':
        setSelectedAction(actionId);
        setShowCommentForm('complete');
        break;
      case 'add_comment':
        setSelectedAction(actionId);
        break;
    }
  };

  const handleTakeInWork = async () => {
    setActionLoading('take_in_work');
    try {
      await dataProvider.create('support_tickets_take_in_work', {
        data: { id: ticketId }
      });
      notify('Заявка успешно взята в работу', { type: 'success' });
      refresh();
    } catch (error) {
      notify('Ошибка при взятии заявки в работу', { type: 'error' });
    } finally {
      setActionLoading(null);
    }
  };

  const handleChangeToInProgress = async () => {
    setActionLoading('change_to_in_progress');
    try {
      await dataProvider.create('support_tickets_change_to_in_progress', {
        data: {
          comment: '',
          activityId: 'Activity_1ht60dz',
          supportTicket: ticketId,
        }
      });
      notify('Статус заявки успешно изменен на "В работе"', { type: 'success' });
      refresh();
    } catch (error) {
      notify('Ошибка при изменении статуса заявки', { type: 'error' });
    } finally {
      setActionLoading(null);
    }
  };

  const getActionName = (actionId: string) => {
    const actionOptions = getActionOptions();
    const action = actionOptions.find(option => option.id === actionId);
    return action ? action.name : 'Выберите действие...';
  };

  const renderActionButtons = () => {
    // If no process instance key, show nothing
    if (!processInstanceKey) return null;

    const actionOptions = getActionOptions();

    if (actionOptions.length === 0) return null;

    // For new tickets and postponed tickets, show button instead of dropdown
    if ((currentStatus === 'new' || currentStatus === 'postponed') && actionOptions.length === 1) {
      const option = actionOptions[0];
      return (
        <Button
          variant="contained"
          color="primary"
          onClick={() => handleActionSelect(option.id)}
          disabled={!!actionLoading}
          startIcon={actionLoading === option.id ? <CircularProgress size={16} color="inherit" /> : null}
        >
          {actionLoading === option.id ? 'Обработка...' : option.name}
        </Button>
      );
    }

    // For other statuses, show dropdown
    return (
      <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
        <Select
          value={selectedAction}
          onChange={(e) => handleActionSelect(e.target.value)}
          displayEmpty
          disabled={!!actionLoading}
          size="small"
          renderValue={(value) => value ? getActionName(value) : 'Выберите действие...'}
        >
          <MenuItem value="" disabled>
            Выберите действие...
          </MenuItem>
          {actionOptions.map(option => (
            <MenuItem key={option.id} value={option.id}>
              {option.name}
            </MenuItem>
          ))}
        </Select>

        {/* Form for adding comment when "Add comment" is selected */}
        {selectedAction === 'add_comment' && (
          <Box sx={{ width: '100%', mt: 2 }}>
            <Typography variant="body2" sx={{ mb: 1 }}>Комментарий</Typography>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={3}
              style={{
                width: '100%',
                maxWidth: '600px',
                padding: '8px 12px',
                border: '1px solid #ccc',
                borderRadius: '4px',
                fontFamily: 'inherit',
                fontSize: '14px',
                resize: 'vertical'
              }}
              placeholder="Добавьте комментарий..."
            />
            <Box sx={{ display: 'flex', justifyContent: 'flex-start', mt: 1 }}>
              <Button
                variant="outlined"
                onClick={() => handleAction('add_comment', undefined, currentStatus)}
                disabled={!comment.trim() || !!actionLoading}
                startIcon={actionLoading === 'add_comment' ? <CircularProgress size={16} color="inherit" /> : null}
              >
                {actionLoading === 'add_comment' ? 'Добавление...' : 'Добавить комментарий'}
              </Button>
            </Box>
          </Box>
        )}
      </Box>
    );
  };

  const renderCommentForm = () => {
    if (!showCommentForm) return null;

    return (
      <Card sx={{ mt: 2, p: 2 }}>
        <Typography variant="h6" gutterBottom>
          {showCommentForm === 'postpone' ? 'Отложить заявку' : 'Завершить заявку'}
        </Typography>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          <Box>
            <Typography variant="body2" sx={{ mb: 1 }}>Комментарий</Typography>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={4}
              style={{
                width: '100%',
                maxWidth: '600px',
                padding: '8px 12px',
                border: '1px solid #ccc',
                borderRadius: '4px',
                fontFamily: 'inherit',
                fontSize: '14px',
                resize: 'vertical'
              }}
              placeholder="Добавьте комментарий..."
            />
          </Box>
          {showCommentForm === 'complete' && (
            <Box>
              <Typography variant="body2" sx={{ mb: 1 }}>Причина закрытия</Typography>
              <select
                value={closingReason}
                onChange={(e) => setClosingReason(e.target.value)}
                style={{
                  width: '100%',
                  maxWidth: '200px',
                  padding: '8px 12px',
                  border: '1px solid #ccc',
                  borderRadius: '4px',
                  fontFamily: 'inherit',
                  fontSize: '14px'
                }}
              >
                <option value="">Выберите причину...</option>
                {closingReasonChoices.map(choice => (
                  <option key={choice.id} value={choice.id}>{choice.name}</option>
                ))}
              </select>
            </Box>
          )}
          <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-start' }}>
            <Button
              variant="outlined"
              onClick={() => {
                setShowCommentForm(null);
                setComment('');
                setClosingReason('');
              }}
            >
              Отмена
            </Button>
            <Button
              variant="contained"
              color="primary"
              onClick={() => handleAction(
                showCommentForm,
                'Activity_0ioq91f',
                showCommentForm === 'postpone' ? 'postponed' : 'completed',
                showCommentForm === 'complete' ? closingReason : undefined
              )}
              disabled={actionLoading === showCommentForm || (showCommentForm === 'complete' && !closingReason)}
              startIcon={actionLoading === showCommentForm ? <CircularProgress size={16} color="inherit" /> : null}
            >
              {actionLoading === showCommentForm ? 'Обработка...' :
                showCommentForm === 'postpone' ? 'Отложить' : 'Завершить'}
            </Button>
          </Box>
        </Box>
      </Card>
    );
  };

  return (
    <Box sx={{ mt: 2, mb: 2 }}>
      {renderActionButtons()}
      {renderCommentForm()}
    </Box>
  );
};

const TakeInWorkButton = ({ ticketId, processInstanceKey }: {
  ticketId: string,
  processInstanceKey?: string
}) => {
  const dataProvider = useDataProvider();
  const notify = useNotify();
  const refresh = useRefresh();
  const [isLoading, setIsLoading] = React.useState(false);

  const handleTakeInWork = async () => {
    setIsLoading(true);
    try {
      // Extract numeric ID from IRI if needed
      const numericId = ticketId.toString().split('/').pop();
      await dataProvider.create('support_tickets_take_in_work', {
        data: { id: numericId }
      });
      notify('Заявка успешно взята в работу', { type: 'success' });
      refresh();
    } catch (error) {
      notify('Ошибка при взятии заявки в работу', { type: 'error' });
    } finally {
      setIsLoading(false);
    }
  };

  // Show button only if ticket has process instance key
  if (!processInstanceKey) {
    return null;
  }

  return (
    <Button
      variant="contained"
      color="primary"
      onClick={handleTakeInWork}
      size="small"
      startIcon={isLoading ? <CircularProgress size={16} color="inherit" /> : <PersonAddIcon />}
      disabled={isLoading}
    >
      {isLoading ? 'Обработка...' : 'Взять в работу'}
    </Button>
  );
};

const CommentsTimeline = ({ ticketId, processInstanceKey, statusColors }: { ticketId: string, processInstanceKey?: string, statusColors: Record<string, string> }) => {
  const { data: comments, isLoading, error } = useGetList(
    'support_ticket_comments',
    {
      filter: { 'supportTicket': ticketId },
      sort: { field: 'createdAt', order: 'DESC' },
      pagination: { page: 1, perPage: 100 }
    }
  );

  if (isLoading) {
    return <CircularProgress />;
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
              <TimelineOppositeContent sx={{ m: 'auto 0' }}>
                <Typography
                  variant="body2"
                  sx={{
                    color: `${statusColor}.main`,
                    fontWeight: 'bold'
                  }}
                >
                  {comment.statusDisplayName}
                </Typography>
                <Box sx={{ mt: 1 }}>
                  {comment.closingReason && (
                    <Typography variant="body2" color="secondary">
                      {comment.closingReasonDisplayName}
                    </Typography>
                  )}
                </Box>
              </TimelineOppositeContent>
              <TimelineSeparator>
                <TimelineConnector />
                <TimelineDot />
                <TimelineConnector />
              </TimelineSeparator>
              <TimelineContent sx={{ py: '12px', px: 2 }}>
                <Box sx={{ mt: 1 }}>
                  <Typography variant="body2" color="secondary">
                    <Tooltip title={new Date(comment.createdAt).toLocaleString('ru-RU')}>
                      <span>{formatDistanceToNow(new Date(comment.createdAt), { addSuffix: true, locale: ru })}</span>
                    </Tooltip>
                  </Typography>
                </Box>
                <Typography variant="body1" sx={{ mt: 1 }}>
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
  return <TopToolbar />;
};

const SupportTicketTitle = () => {
  const { record } = useShowContext();
  return <span>Заявка {record?.subject || 'Заявка'}</span>;
};

const SupportTicketShowContent = () => {
  const { record } = useShowContext();
  const dataProvider = useDataProvider();
  const [closingReasonChoices, setClosingReasonChoices] = React.useState<any[]>([]);
  const [statusColors, setStatusColors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    const loadData = async () => {
      try {
        const [reasons, statuses] = await Promise.all([
          (dataProvider as any).getSupportTicketClosingReasons(),
          (dataProvider as any).getSupportTicketStatuses()
        ]);
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
  }, [dataProvider]);

  return (
    <SimpleShowLayout
      sx={{
        '& .RaSimpleShowLayout-row': {
          borderBottom: '1px solid #e0e0e0',
          padding: '16px 0',
        },
        '& .RaSimpleShowLayout-label': {
          fontWeight: 'bold',
          fontSize: '0.875rem',
          color: '#1976d2',
          textTransform: 'uppercase',
          letterSpacing: '0.5px',
          minWidth: '200px',
        },
        '& .RaSimpleShowLayout-value': {
          fontSize: '1rem',
          color: '#333',
        },
      }}
    >
      <TextField source="subject" label="Причина обращения" />
      <TextField source="description" label="Цель обращения" />
      {/*<DateField source="createdAt" label="Создана" showTime/>*/}
      <FunctionField
        label="Статус"
        render={(record: any) => <StatusChip status={record?.currentStatus} statusValue={record?.currentStatusValue} color={statusColors[record?.currentStatusValue]} />}
      />
      <FunctionField
        label="Информация о заказе"
        render={(record: any) => record?.orderId &&
          <OrderInfo orderId={record.orderId} orderData={record.orderData}/>}
      />
      <FunctionField
        label="Действия"
        render={(record: any) => record?.id && record?.processInstanceKey &&
          <TicketActions ticketId={record.id} processInstanceKey={record.processInstanceKey}
            currentStatus={record.currentStatusValue} closingReasonChoices={closingReasonChoices} />}
      />
      <FunctionField
        label="Активность"
        render={(record: any) => record?.id &&
          <CommentsTimeline ticketId={record.id} processInstanceKey={record.processInstanceKey} statusColors={statusColors} />}
      />
    </SimpleShowLayout>
  );
};

export const SupportTicketShow = () => (
  <Show title={<SupportTicketTitle />} actions={<SupportTicketActions />}>
    <SupportTicketShowContent />
  </Show>
);
