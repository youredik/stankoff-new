import React, {useState, useCallback} from 'react';
import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  CircularProgress,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';
import DownloadIcon from '@mui/icons-material/Download';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip as RechartsTooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import {
  exportEmployeeSummary,
  type HourlyDistributionResponse,
  type EmployeeSummaryResponse,
} from '../../services/analyticsService';

const formatMinutes = (minutes: number | null): string => {
  if (minutes === null) return '—';
  const totalMinutes = Math.round(minutes);
  const days = Math.floor(totalMinutes / (60 * 24));
  const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
  const mins = totalMinutes % 60;

  let result = '';
  if (days > 0) result += `${days}д `;
  if (hours > 0) result += `${hours}ч `;
  if (mins > 0 || result === '') result += `${mins}м`;
  return result.trim();
};

interface ActivityTabProps {
  fromDate: string;
  toDate: string;
  hourlyData: HourlyDistributionResponse | null;
  employeeData: EmployeeSummaryResponse | null;
  isManager: boolean;
  loading: boolean;
}

export const ActivityTab = ({fromDate, toDate, hourlyData, employeeData, isManager, loading}: ActivityTabProps) => {
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleExport = useCallback(async () => {
    setExporting(true);
    setError(null);
    try {
      await exportEmployeeSummary(fromDate, toDate);
    } catch (err: any) {
      setError(err.message || 'Не удалось скачать файл');
    } finally {
      setExporting(false);
    }
  }, [fromDate, toDate]);

  if (loading) {
    return (
      <Box sx={{display: 'flex', justifyContent: 'center', py: 6}}>
        <CircularProgress />
      </Box>
    );
  }

  if (!hourlyData) {
    return <Typography color="text.secondary">Нажмите «Сформировать» для загрузки данных</Typography>;
  }

  const chartData = hourlyData.ticketsByHour.map((tickets, hour) => ({
    hour: `${hour}:00`,
    'Обращения клиентов': tickets,
    'Активность сотрудников': hourlyData.activityByHour[hour] || 0,
  }));

  return (
    <Box>
      {error && (
        <Alert severity="error" sx={{mb: 2}} onClose={() => setError(null)}>
          {error}
        </Alert>
      )}

      <Card variant="outlined" sx={{mb: 3}}>
        <CardContent>
          <Typography variant="subtitle1" sx={{fontWeight: 600, mb: 2}}>
            Распределение обращений по часам
          </Typography>
          <ResponsiveContainer width="100%" height={350}>
            <BarChart data={chartData} margin={{top: 5, right: 20, left: 0, bottom: 5}}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="hour" fontSize={12} />
              <YAxis allowDecimals={false} />
              <RechartsTooltip />
              <Legend />
              <Bar dataKey="Обращения клиентов" fill="#1976d2" radius={[2, 2, 0, 0]} />
              <Bar dataKey="Активность сотрудников" fill="#ff9800" radius={[2, 2, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </CardContent>
      </Card>

      {isManager && employeeData && (
        <Card variant="outlined">
          <CardContent>
            <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2}}>
              <Typography variant="subtitle1" sx={{fontWeight: 600}}>
                Сводка по сотрудникам
              </Typography>
              {employeeData.rows.length > 0 && (
                <Button
                  variant="outlined"
                  size="small"
                  startIcon={exporting ? <CircularProgress size={16} /> : <DownloadIcon />}
                  onClick={handleExport}
                  disabled={exporting}
                >
                  Скачать CSV
                </Button>
              )}
            </Box>
            {employeeData.rows.length === 0 ? (
              <Typography color="text.secondary">Нет данных</Typography>
            ) : (
              <TableContainer>
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell sx={{fontWeight: 600}}>Сотрудник</TableCell>
                      <TableCell sx={{fontWeight: 600}} align="center">Завершено</TableCell>
                      <TableCell sx={{fontWeight: 600}} align="center">Ср. время принятия</TableCell>
                      <TableCell sx={{fontWeight: 600}} align="center">Ср. время решения</TableCell>
                      <TableCell sx={{fontWeight: 600}} align="center">Просрочено принятие</TableCell>
                      <TableCell sx={{fontWeight: 600}} align="center">Просрочено решение</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {employeeData.rows.map((row) => {
                      const avgAcceptMinutes = row.avg_acceptance_minutes !== null ? Number(row.avg_acceptance_minutes) : null;
                      const avgResolveMinutes = row.avg_resolution_minutes !== null ? Number(row.avg_resolution_minutes) : null;
                      const acceptOverdue = avgAcceptMinutes !== null && avgAcceptMinutes > 120;
                      const resolveOverdue = avgResolveMinutes !== null && avgResolveMinutes > 2880;

                      return (
                        <TableRow key={row.user_id} hover>
                          <TableCell>
                            <Typography variant="body2" fontWeight={500}>{row.user_name}</Typography>
                          </TableCell>
                          <TableCell align="center">{row.completed_count}</TableCell>
                          <TableCell align="center">
                            <Box component="span" sx={{color: acceptOverdue ? '#f44336' : '#4caf50', fontWeight: 600}}>
                              {formatMinutes(avgAcceptMinutes)}
                            </Box>
                          </TableCell>
                          <TableCell align="center">
                            <Box component="span" sx={{color: resolveOverdue ? '#f44336' : '#4caf50', fontWeight: 600}}>
                              {formatMinutes(avgResolveMinutes)}
                            </Box>
                          </TableCell>
                          <TableCell align="center">
                            <Box component="span" sx={{color: Number(row.acceptance_overdue_count) > 0 ? '#f44336' : undefined, fontWeight: Number(row.acceptance_overdue_count) > 0 ? 600 : undefined}}>
                              {row.acceptance_overdue_count}
                            </Box>
                          </TableCell>
                          <TableCell align="center">
                            <Box component="span" sx={{color: Number(row.resolution_overdue_count) > 0 ? '#f44336' : undefined, fontWeight: Number(row.resolution_overdue_count) > 0 ? 600 : undefined}}>
                              {row.resolution_overdue_count}
                            </Box>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </TableContainer>
            )}
          </CardContent>
        </Card>
      )}
    </Box>
  );
};
