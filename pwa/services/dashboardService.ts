import {authenticatedFetch} from '../utils/authenticatedFetch';

export interface DashboardStats {
  period: string;
  periodStart: string;
  periodEnd: string;
  daysInPeriod: number;
  ticketsCompleted: number;
  ticketsTotal: number;
  ticketsCompletedPerDay: number;
  averageHandlingTimeMinutes: number | null;
  overduePercent: number;
  overdueCount: number;
  byStatus: Record<string, number>;
  targets: {
    ticketsPerDay: number;
    maxHandlingTimeMinutes: number;
    maxOverduePercent: number;
  };
}

export interface EmployeeStats {
  id: number;
  name: string;
  ticketsCompleted: number;
  ticketsCompletedPerDay: number;
  averageHandlingTimeMinutes: number | null;
  overduePercent: number;
}

export interface EmployeesResponse {
  period: string;
  employees: EmployeeStats[];
  targets: {
    ticketsPerDay: number;
    maxHandlingTimeMinutes: number;
    maxOverduePercent: number;
  };
}

export const getStats = async (userId?: number, period: string = 'today'): Promise<DashboardStats> => {
  const params = new URLSearchParams({period});
  if (userId) {
    params.set('userId', String(userId));
  }
  const response = await authenticatedFetch(`/api/dashboard/stats?${params}`);
  return response.json();
};

export const getEmployees = async (period: string = 'today'): Promise<EmployeesResponse> => {
  const params = new URLSearchParams({period});
  const response = await authenticatedFetch(`/api/dashboard/employees?${params}`);
  return response.json();
};
