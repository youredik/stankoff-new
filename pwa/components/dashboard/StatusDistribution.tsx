import {Box, Chip} from '@mui/material';

const statusLabels: Record<string, string> = {
  new: 'Новые',
  in_progress: 'В работе',
  postponed: 'Отложено',
  completed: 'Завершено',
};

const statusColors: Record<string, 'info' | 'warning' | 'default' | 'success'> = {
  new: 'info',
  in_progress: 'warning',
  postponed: 'default',
  completed: 'success',
};

interface StatusDistributionProps {
  byStatus: Record<string, number>;
}

export const StatusDistribution = ({byStatus}: StatusDistributionProps) => (
  <Box sx={{display: 'flex', gap: 1, flexWrap: 'wrap'}}>
    {Object.entries(byStatus).map(([status, count]) => (
      <Chip
        key={status}
        label={`${statusLabels[status] || status}: ${count}`}
        color={statusColors[status] || 'default'}
        variant="filled"
        size="medium"
      />
    ))}
  </Box>
);
