import React, {useState, useEffect, useCallback} from 'react';
import {
  Box,
  CircularProgress,
  Tab,
  Tabs,
  Typography,
} from '@mui/material';
import AccessTimeIcon from '@mui/icons-material/AccessTime';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import CategoryIcon from '@mui/icons-material/Category';
import BarChartIcon from '@mui/icons-material/BarChart';
import {format, startOfMonth} from 'date-fns';
import {getSession} from 'next-auth/react';
import {type Session} from '../../app/auth';
import {
  getAcceptanceTime,
  getResolutionTime,
  getClosingReasons,
  getHourlyDistribution,
  getEmployeeSummary,
  type AcceptanceTimeResponse,
  type ResolutionTimeResponse,
  type ClosingReasonsResponse,
  type HourlyDistributionResponse,
  type EmployeeSummaryResponse,
} from '../../services/analyticsService';
import {DateRangePicker} from './DateRangePicker';
import {AcceptanceTimeTab} from './AcceptanceTimeTab';
import {ResolutionTimeTab} from './ResolutionTimeTab';
import {ClosingReasonsTab} from './ClosingReasonsTab';
import {ActivityTab} from './ActivityTab';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

const TabPanel = ({children, value, index}: TabPanelProps) => (
  <div role="tabpanel" hidden={value !== index}>
    {value === index && <Box sx={{pt: 2}}>{children}</Box>}
  </div>
);

export const Dashboard = () => {
  const [fromDate, setFromDate] = useState(format(startOfMonth(new Date()), 'yyyy-MM-dd'));
  const [toDate, setToDate] = useState(format(new Date(), 'yyyy-MM-dd'));
  const [activeTab, setActiveTab] = useState(0);
  const [isManager, setIsManager] = useState(false);
  const [loading, setLoading] = useState(false);
  const [userId, setUserId] = useState<number | undefined>(undefined);

  const [acceptanceData, setAcceptanceData] = useState<AcceptanceTimeResponse | null>(null);
  const [resolutionData, setResolutionData] = useState<ResolutionTimeResponse | null>(null);
  const [closingData, setClosingData] = useState<ClosingReasonsResponse | null>(null);
  const [hourlyData, setHourlyData] = useState<HourlyDistributionResponse | null>(null);
  const [employeeData, setEmployeeData] = useState<EmployeeSummaryResponse | null>(null);

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

  const clearData = () => {
    setAcceptanceData(null);
    setResolutionData(null);
    setClosingData(null);
    setHourlyData(null);
    setEmployeeData(null);
  };

  const loadData = useCallback(async () => {
    if (!fromDate || !toDate) return;

    setLoading(true);
    clearData();

    try {
      const results = await Promise.allSettled([
        getAcceptanceTime(fromDate, toDate, userId),
        getResolutionTime(fromDate, toDate, userId),
        getClosingReasons(fromDate, toDate, userId),
        getHourlyDistribution(fromDate, toDate),
        ...(isManager ? [getEmployeeSummary(fromDate, toDate)] : []),
      ]);

      if (results[0].status === 'fulfilled') setAcceptanceData(results[0].value);
      if (results[1].status === 'fulfilled') setResolutionData(results[1].value);
      if (results[2].status === 'fulfilled') setClosingData(results[2].value);
      if (results[3].status === 'fulfilled') setHourlyData(results[3].value as HourlyDistributionResponse);
      if (isManager && results[4]?.status === 'fulfilled') setEmployeeData(results[4].value as EmployeeSummaryResponse);
    } catch (err) {
      console.error('Failed to load analytics data:', err);
    } finally {
      setLoading(false);
    }
  }, [fromDate, toDate, userId, isManager]);

  return (
    <Box sx={{p: 2}}>
      <Typography variant="h5" sx={{mb: 3}}>
        Дашборд
      </Typography>

      <Box sx={{mb: 3}}>
        <DateRangePicker
          fromDate={fromDate}
          toDate={toDate}
          onFromChange={setFromDate}
          onToChange={setToDate}
          onSearch={loadData}
          loading={loading}
        />
      </Box>

      <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
        <Tabs
          value={activeTab}
          onChange={(_, newValue) => setActiveTab(newValue)}
          variant="scrollable"
          scrollButtons="auto"
        >
          <Tab icon={<AccessTimeIcon />} iconPosition="start" label="Принятие заявок" />
          <Tab icon={<CheckCircleIcon />} iconPosition="start" label="Решение заявок" />
          <Tab icon={<CategoryIcon />} iconPosition="start" label="Причины закрытия" />
          <Tab icon={<BarChartIcon />} iconPosition="start" label="Активность" />
        </Tabs>
      </Box>

      <TabPanel value={activeTab} index={0}>
        <AcceptanceTimeTab
          fromDate={fromDate}
          toDate={toDate}
          userId={userId}
          data={acceptanceData}
          onDataLoaded={setAcceptanceData}
          loading={loading}
        />
      </TabPanel>

      <TabPanel value={activeTab} index={1}>
        <ResolutionTimeTab
          fromDate={fromDate}
          toDate={toDate}
          userId={userId}
          data={resolutionData}
          onDataLoaded={setResolutionData}
          loading={loading}
        />
      </TabPanel>

      <TabPanel value={activeTab} index={2}>
        <ClosingReasonsTab
          fromDate={fromDate}
          toDate={toDate}
          userId={userId}
          data={closingData}
          onDataLoaded={setClosingData}
          loading={loading}
        />
      </TabPanel>

      <TabPanel value={activeTab} index={3}>
        <ActivityTab
          fromDate={fromDate}
          toDate={toDate}
          hourlyData={hourlyData}
          employeeData={employeeData}
          isManager={isManager}
          loading={loading}
        />
      </TabPanel>
    </Box>
  );
};
