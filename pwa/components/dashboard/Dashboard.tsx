import React, {useState, useEffect, useCallback} from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  CircularProgress,
  ToggleButton,
  ToggleButtonGroup,
  Typography,
} from '@mui/material';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import {getSession} from 'next-auth/react';
import {type Session} from '../../app/auth';
import {getStats, getEmployees, type DashboardStats, type EmployeesResponse} from '../../services/dashboardService';
import {KpiCard, getKpiStatus} from './KpiCard';
import {StatusDistribution} from './StatusDistribution';
import {EmployeeList} from './EmployeeList';

type Period = 'today' | 'week' | 'month';

const periodLabels: Record<Period, string> = {
  today: 'Сегодня',
  week: 'Неделя',
  month: 'Месяц',
};

const formatMinutes = (minutes: number | null): string => {
  if (minutes === null) return '—';
  if (minutes < 60) return `${Math.round(minutes)} мин`;
  const hours = Math.floor(minutes / 60);
  const mins = Math.round(minutes % 60);
  return mins > 0 ? `${hours}ч ${mins}м` : `${hours}ч`;
};

const formatTargetMinutes = (minutes: number): string => {
  if (minutes < 60) return `${minutes} мин`;
  const hours = minutes / 60;
  return `${hours}ч`;
};

const StatsView = ({stats}: {stats: DashboardStats}) => {
  const {targets} = stats;

  const completedPerDayStatus = getKpiStatus(stats.ticketsCompletedPerDay, targets.ticketsPerDay);
  const completedPerDayProgress = (stats.ticketsCompletedPerDay / targets.ticketsPerDay) * 100;

  const avgTimeStatus = stats.averageHandlingTimeMinutes !== null
    ? getKpiStatus(stats.averageHandlingTimeMinutes, targets.maxHandlingTimeMinutes, true)
    : 'success';
  const avgTimeProgress = stats.averageHandlingTimeMinutes !== null
    ? Math.min((targets.maxHandlingTimeMinutes / stats.averageHandlingTimeMinutes) * 100, 100)
    : 100;

  const overdueStatus = getKpiStatus(stats.overduePercent, targets.maxOverduePercent, true);
  const overdueProgress = stats.overduePercent > 0
    ? Math.min((targets.maxOverduePercent / stats.overduePercent) * 100, 100)
    : 100;

  return (
    <Box>
      <Box sx={{display: 'flex', gap: 2, mb: 3, flexWrap: 'wrap'}}>
        <KpiCard
          title="Заявок в день"
          value={stats.ticketsCompletedPerDay}
          target={`Норма: ${targets.ticketsPerDay}`}
          progress={completedPerDayProgress}
          status={completedPerDayStatus}
          subtitle={`Всего завершено за период: ${stats.ticketsCompleted}`}
        />
        <KpiCard
          title="Среднее время обработки"
          value={formatMinutes(stats.averageHandlingTimeMinutes)}
          target={`Норма: ${formatTargetMinutes(targets.maxHandlingTimeMinutes)}`}
          progress={avgTimeProgress}
          status={avgTimeStatus}
        />
        <KpiCard
          title="Просрочено"
          value={`${stats.overduePercent}%`}
          target={`Макс: ${targets.maxOverduePercent}%`}
          progress={overdueProgress}
          status={overdueStatus}
          subtitle={`${stats.overdueCount} из ${stats.ticketsCompleted} заявок`}
        />
      </Box>

      <Card variant="outlined">
        <CardContent>
          <Typography variant="subtitle1" sx={{fontWeight: 600, mb: 1.5}}>
            Распределение по статусам
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{mb: 1}}>
            Всего заявок за период: {stats.ticketsTotal}
          </Typography>
          <StatusDistribution byStatus={stats.byStatus} />
        </CardContent>
      </Card>
    </Box>
  );
};

export const Dashboard = () => {
  const [period, setPeriod] = useState<Period>('today');
  const [isManager, setIsManager] = useState(false);
  const [selectedEmployeeId, setSelectedEmployeeId] = useState<number | null>(null);
  const [selectedEmployeeName, setSelectedEmployeeName] = useState<string>('');
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [employeesData, setEmployeesData] = useState<EmployeesResponse | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const checkRole = async () => {
      const session = await getSession() as Session | null;
      const roles = session?.user?.roles || [];
      const hasManagerAccess = roles.some(role => {
        const r = role.toLowerCase();
        return r === 'support_manager' || r === 'admin';
      });
      setIsManager(hasManagerAccess);
    };
    checkRole();
  }, []);

  const loadData = useCallback(async () => {
    setLoading(true);
    try {
      if (isManager && selectedEmployeeId === null) {
        const data = await getEmployees(period);
        setEmployeesData(data);
        setStats(null);
      } else {
        const userId = selectedEmployeeId ?? undefined;
        const data = await getStats(userId, period);
        setStats(data);
        setEmployeesData(null);
      }
    } catch (err) {
      console.error('Failed to load dashboard data:', err);
    } finally {
      setLoading(false);
    }
  }, [period, isManager, selectedEmployeeId]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const handleSelectEmployee = (id: number) => {
    const emp = employeesData?.employees.find(e => e.id === id);
    setSelectedEmployeeId(id);
    setSelectedEmployeeName(emp?.name || '');
  };

  const handleBack = () => {
    setSelectedEmployeeId(null);
    setSelectedEmployeeName('');
  };

  return (
    <Box sx={{p: 2}}>
      <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 3, flexWrap: 'wrap', gap: 2}}>
        <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
          {isManager && selectedEmployeeId !== null && (
            <Button startIcon={<ArrowBackIcon />} onClick={handleBack} size="small">
              Назад
            </Button>
          )}
          <Typography variant="h5">
            {isManager && selectedEmployeeId === null
              ? 'Дашборд сотрудников'
              : selectedEmployeeName
                ? `Дашборд: ${selectedEmployeeName}`
                : 'Мой дашборд'}
          </Typography>
        </Box>

        <ToggleButtonGroup
          value={period}
          exclusive
          onChange={(_, value) => value && setPeriod(value)}
          size="small"
        >
          {Object.entries(periodLabels).map(([key, label]) => (
            <ToggleButton key={key} value={key}>{label}</ToggleButton>
          ))}
        </ToggleButtonGroup>
      </Box>

      {loading ? (
        <Box sx={{display: 'flex', justifyContent: 'center', py: 6}}>
          <CircularProgress />
        </Box>
      ) : isManager && selectedEmployeeId === null && employeesData ? (
        <Card variant="outlined">
          <CardContent>
            <EmployeeList
              employees={employeesData.employees}
              targets={employeesData.targets}
              onSelectEmployee={handleSelectEmployee}
            />
          </CardContent>
        </Card>
      ) : stats ? (
        <StatsView stats={stats} />
      ) : (
        <Typography color="text.secondary">Нет данных</Typography>
      )}
    </Box>
  );
};
