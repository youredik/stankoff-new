import {Box, Card, CardContent, LinearProgress, Typography} from '@mui/material';

type KpiStatus = 'success' | 'warning' | 'error';

interface KpiCardProps {
  title: string;
  value: string | number;
  target: string;
  progress: number; // 0-100, can exceed 100
  status: KpiStatus;
  subtitle?: string;
}

const statusColors: Record<KpiStatus, string> = {
  success: '#4caf50',
  warning: '#ff9800',
  error: '#f44336',
};

export const KpiCard = ({title, value, target, progress, status, subtitle}: KpiCardProps) => (
  <Card variant="outlined" sx={{flex: 1, minWidth: 200}}>
    <CardContent>
      <Typography variant="body2" color="text.secondary" gutterBottom>
        {title}
      </Typography>
      <Typography variant="h4" sx={{color: statusColors[status], fontWeight: 700}}>
        {value}
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{mb: 1}}>
        {target}
      </Typography>
      <LinearProgress
        variant="determinate"
        value={Math.min(progress, 100)}
        sx={{
          height: 6,
          borderRadius: 3,
          backgroundColor: '#e0e0e0',
          '& .MuiLinearProgress-bar': {
            backgroundColor: statusColors[status],
            borderRadius: 3,
          },
        }}
      />
      {subtitle && (
        <Typography variant="caption" color="text.secondary" sx={{mt: 0.5, display: 'block'}}>
          {subtitle}
        </Typography>
      )}
    </CardContent>
  </Card>
);

export const getKpiStatus = (value: number, target: number, inverted: boolean = false): KpiStatus => {
  const ratio = inverted ? target / Math.max(value, 0.01) : value / Math.max(target, 0.01);
  if (inverted) {
    if (value <= target) return 'success';
    if (value <= target * 1.5) return 'warning';
    return 'error';
  }
  if (ratio >= 1) return 'success';
  if (ratio >= 0.7) return 'warning';
  return 'error';
};
