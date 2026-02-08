import {
  Box,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';
import {type EmployeeStats} from '../../services/dashboardService';
import {getKpiStatus} from './KpiCard';

interface EmployeeListProps {
  employees: EmployeeStats[];
  targets: {
    ticketsPerDay: number;
    maxHandlingTimeMinutes: number;
    maxOverduePercent: number;
  };
  onSelectEmployee: (id: number) => void;
}

const statusColors = {
  success: '#4caf50',
  warning: '#ff9800',
  error: '#f44336',
};

const formatMinutes = (minutes: number | null): string => {
  if (minutes === null) return '—';
  if (minutes < 60) return `${Math.round(minutes)} мин`;
  const hours = Math.floor(minutes / 60);
  const mins = Math.round(minutes % 60);
  return mins > 0 ? `${hours}ч ${mins}м` : `${hours}ч`;
};

export const EmployeeList = ({employees, targets, onSelectEmployee}: EmployeeListProps) => (
  <TableContainer>
    <Table size="small">
      <TableHead>
        <TableRow>
          <TableCell>Сотрудник</TableCell>
          <TableCell align="center">Завершено</TableCell>
          <TableCell align="center">В день</TableCell>
          <TableCell align="center">Ср. время</TableCell>
          <TableCell align="center">Просрочено</TableCell>
        </TableRow>
      </TableHead>
      <TableBody>
        {employees.map((emp) => {
          const completedStatus = getKpiStatus(emp.ticketsCompletedPerDay, targets.ticketsPerDay);
          const timeStatus = emp.averageHandlingTimeMinutes !== null
            ? getKpiStatus(emp.averageHandlingTimeMinutes, targets.maxHandlingTimeMinutes, true)
            : 'success';
          const overdueStatus = getKpiStatus(emp.overduePercent, targets.maxOverduePercent, true);

          return (
            <TableRow
              key={emp.id}
              hover
              sx={{cursor: 'pointer'}}
              onClick={() => onSelectEmployee(emp.id)}
            >
              <TableCell>
                <Typography variant="body2" fontWeight={500}>{emp.name}</Typography>
              </TableCell>
              <TableCell align="center">{emp.ticketsCompleted}</TableCell>
              <TableCell align="center">
                <Box component="span" sx={{color: statusColors[completedStatus], fontWeight: 600}}>
                  {emp.ticketsCompletedPerDay}
                </Box>
                <Typography variant="caption" color="text.secondary"> / {targets.ticketsPerDay}</Typography>
              </TableCell>
              <TableCell align="center">
                <Box component="span" sx={{color: statusColors[timeStatus], fontWeight: 600}}>
                  {formatMinutes(emp.averageHandlingTimeMinutes)}
                </Box>
              </TableCell>
              <TableCell align="center">
                <Box component="span" sx={{color: statusColors[overdueStatus], fontWeight: 600}}>
                  {emp.overduePercent}%
                </Box>
              </TableCell>
            </TableRow>
          );
        })}
        {employees.length === 0 && (
          <TableRow>
            <TableCell colSpan={5} align="center">
              <Typography variant="body2" color="text.secondary">Нет данных</Typography>
            </TableCell>
          </TableRow>
        )}
      </TableBody>
    </Table>
  </TableContainer>
);
