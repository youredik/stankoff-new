import {Chip} from '@mui/material';

export const getStatusColor = (status: string) => {
  switch (status) {
    case 'new':
      return 'info'; // Blue for new
    case 'in_progress':
      return 'warning'; // Orange for in progress
    case 'postponed':
      return 'secondary'; // Gray for postponed
    case 'completed':
      return 'success'; // Green for completed
    default:
      return 'primary';
  }
};

export const StatusChip = ({status, statusValue, color}: { status: string, statusValue: string, color?: string }) => {
  const chipColor = color || (getStatusColor(statusValue) as any);
  return (
    <Chip
      label={status}
      color={chipColor as any}
      size="small"
      variant="filled"
      sx={{
        fontSize: '0.75rem',
      }}
    />
  );
};
