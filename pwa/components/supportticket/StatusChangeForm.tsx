import React, {useState} from 'react';
import {
  Box,
  Button,
  CircularProgress,
  FormControl,
  InputLabel,
  MenuItem,
  Select,
  TextField,
  Typography
} from '@mui/material';
import {useNotify, useShowContext} from 'react-admin';
import {changeStatus, getClosingReasons, getStatuses} from '../../services/supportTicketService';

interface StatusOption {
  id: string;
  name: string;
  color: string;
}

interface ClosingReasonOption {
  id: string;
  name: string;
}

export const StatusChangeForm = ({onStatusChanged}: { onStatusChanged?: () => void }) => {
  const {record, refetch} = useShowContext();
  const notify = useNotify();
  const [status, setStatus] = useState('');
  const [comment, setComment] = useState('');
  const [closingReason, setClosingReason] = useState('');
  const [statusOptions, setStatusOptions] = useState<StatusOption[]>([]);
  const [closingReasonOptions, setClosingReasonOptions] = useState<ClosingReasonOption[]>([]);
  const [loading, setLoading] = useState(false);

  const currentStatus = record?.status;
  const isCompleted = currentStatus === 'completed';

  // Update status when current status changes
  React.useEffect(() => {
    if (currentStatus) {
      setStatus(currentStatus);
    }
  }, [currentStatus]);

  React.useEffect(() => {
    const loadOptions = async () => {
      try {
        const [statuses, reasons] = await Promise.all([
          getStatuses(),
          getClosingReasons()
        ]);
        setStatusOptions(statuses);
        setClosingReasonOptions(reasons);
      } catch (err) {
        console.error('Failed to load options:', err);
      }
    };
    loadOptions();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const requiresComment = status !== currentStatus && !(currentStatus === 'new' && status === 'in_progress') && !(currentStatus === 'postponed' && status === 'in_progress');
    if (!status || (requiresComment && !comment.trim())) return;

    setLoading(true);

    try {
      const ticketId = typeof record?.id === 'string'
        ? record.id.split('/').pop()
        : record?.id;
      if (!ticketId) {
        throw new Error('Не найден ID заявки');
      }
      await changeStatus(String(ticketId), {status: status, comment: comment, closingReason: closingReason});

      notify('Статус успешно изменен', {type: 'success'});
      setStatus('');
      setComment('');
      setClosingReason('');
      // Refetch the record to update the UI
      refetch();
      onStatusChanged?.();
    } catch (err: any) {
      notify(err.message, {type: 'error'});
    } finally {
      setLoading(false);
    }
  };

  // Determine available statuses based on current status
  const getAvailableStatuses = () => {
    if (!currentStatus) return statusOptions;

    if (currentStatus === 'new') {
      return statusOptions.filter(s => s.id === 'in_progress');
    }

    if (currentStatus === 'completed') {
      return []; // No changes allowed
    }

    // For other statuses, allow all except 'new'
    return statusOptions.filter(s => s.id !== 'new');
  };

  const availableStatuses = getAvailableStatuses();
  const isStatusDisabled = (statusId: string) => {
    if (statusId === 'new') return true; // Always disable 'new' status
    return !availableStatuses.some(s => s.id === statusId);
  };

  if (isCompleted) {
    return (
      <Box sx={{mb: 3}}>
        <Typography variant="body2" color="text.secondary">
          Заявка завершена
        </Typography>
      </Box>
    );
  }

  return (
    <Box sx={{mb: 3}}>
      {/*<Typography variant="h6" gutterBottom>
        Изменение статуса
      </Typography>*/}

      <Box component="form" onSubmit={handleSubmit}
           sx={{display: 'flex', flexDirection: 'column', gap: 2, maxWidth: 600}}>
        <FormControl required sx={{maxWidth: 300}}>
          <InputLabel>Статус</InputLabel>
          <Select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            label="Новый статус"
          >
            {statusOptions.map((statusOption) => (
              <MenuItem
                key={statusOption.id}
                value={statusOption.id}
                disabled={isStatusDisabled(statusOption.id)}
              >
                {statusOption.name}
              </MenuItem>
            ))}
          </Select>
        </FormControl>

        <TextField
          label="Комментарий"
          multiline
          rows={3}
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          placeholder={currentStatus === 'new' && status === 'new' ? "Сначала выберите статус 'В работе'" : "Опишите изменения или причину смены статуса..."}
          required={status !== currentStatus && !(currentStatus === 'new' && status === 'in_progress') && !(currentStatus === 'postponed' && status === 'in_progress')}
          disabled={currentStatus === 'new' && status === 'new'}
          sx={{maxWidth: 600}}
        />

        {status === 'completed' && (
          <FormControl required sx={{maxWidth: 400}}>
            <InputLabel>Причина закрытия</InputLabel>
            <Select
              value={closingReason}
              onChange={(e) => setClosingReason(e.target.value)}
              label="Причина закрытия"
            >
              {closingReasonOptions.map((reason) => (
                <MenuItem key={reason.id} value={reason.id}>
                  {reason.name}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
        )}

        <Box sx={{display: 'flex', justifyContent: 'flex-end'}}>
          <Button
            type="submit"
            variant="contained"
            disabled={loading || !status || (status !== currentStatus && !(currentStatus === 'new' && status === 'in_progress') && !(currentStatus === 'postponed' && status === 'in_progress') && !comment.trim())}
            startIcon={loading ? <CircularProgress size={20}/> : null}
          >
            {loading ? 'Сохранение...' : 'Отправить'}
          </Button>
        </Box>
      </Box>
    </Box>
  );
};
