import {authenticatedFetch} from '../utils/authenticatedFetch';

export interface AcceptanceTimeRow {
  id: number;
  subject: string;
  contractor: string | null;
  user_name: string | null;
  acceptance_time_minutes: number;
}

export interface AcceptanceTimeResponse {
  from: string;
  to: string;
  total: number;
  withinSla: number;
  overdue: number;
  slaMinutes: number;
  rows: AcceptanceTimeRow[];
}

export interface ResolutionTimeRow {
  id: number;
  subject: string;
  contractor: string | null;
  user_name: string | null;
  resolution_time_minutes: number;
}

export interface ResolutionTimeResponse {
  from: string;
  to: string;
  total: number;
  withinSla: number;
  overdue: number;
  slaMinutes: number;
  rows: ResolutionTimeRow[];
}

export interface ClosingReasonRow {
  id: number;
  subject: string;
  contractor: string | null;
  user_name: string | null;
  closing_reason: string;
  closing_reason_label: string;
}

export interface ClosingReasonsResponse {
  from: string;
  to: string;
  total: number;
  counts: Record<string, number>;
  reasonLabels: Record<string, string>;
  rows: ClosingReasonRow[];
}

export interface HourlyDistributionResponse {
  from: string;
  to: string;
  ticketsByHour: number[];
  activityByHour: number[];
}

export interface EmployeeSummaryRow {
  user_id: number;
  user_name: string;
  completed_count: number;
  avg_acceptance_minutes: number | null;
  avg_resolution_minutes: number | null;
  acceptance_overdue_count: number;
  resolution_overdue_count: number;
}

export interface EmployeeSummaryResponse {
  from: string;
  to: string;
  sla: {
    acceptanceMinutes: number;
    resolutionMinutes: number;
  };
  rows: EmployeeSummaryRow[];
}

const buildParams = (from: string, to: string, userId?: number): string => {
  const params = new URLSearchParams({from, to});
  if (userId !== undefined) {
    params.set('userId', String(userId));
  }
  return params.toString();
};

const fetchJson = async <T>(url: string): Promise<T> => {
  const response = await authenticatedFetch(url);
  if (!response.ok) {
    throw new Error('Не удалось загрузить данные');
  }
  return response.json();
};

const downloadCsv = async (url: string, defaultFilename: string): Promise<void> => {
  const response = await authenticatedFetch(url);
  if (!response.ok) {
    throw new Error('Не удалось выгрузить отчет');
  }
  const blob = await response.blob();
  const blobUrl = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = blobUrl;
  a.download = defaultFilename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(blobUrl);
};

export const getAcceptanceTime = (from: string, to: string, userId?: number): Promise<AcceptanceTimeResponse> =>
  fetchJson(`/api/reports/analytics/acceptance-time?${buildParams(from, to, userId)}`);

export const getResolutionTime = (from: string, to: string, userId?: number): Promise<ResolutionTimeResponse> =>
  fetchJson(`/api/reports/analytics/resolution-time?${buildParams(from, to, userId)}`);

export const getClosingReasons = (from: string, to: string, userId?: number): Promise<ClosingReasonsResponse> =>
  fetchJson(`/api/reports/analytics/closing-reasons?${buildParams(from, to, userId)}`);

export const getHourlyDistribution = (from: string, to: string): Promise<HourlyDistributionResponse> =>
  fetchJson(`/api/reports/analytics/hourly-distribution?${buildParams(from, to)}`);

export const getEmployeeSummary = (from: string, to: string): Promise<EmployeeSummaryResponse> =>
  fetchJson(`/api/reports/analytics/employee-summary?${buildParams(from, to)}`);

export const exportAcceptanceTime = (from: string, to: string, userId?: number): Promise<void> =>
  downloadCsv(`/api/reports/analytics/acceptance-time/export?${buildParams(from, to, userId)}`, `acceptance_time_${from}_${to}.csv`);

export const exportResolutionTime = (from: string, to: string, userId?: number): Promise<void> =>
  downloadCsv(`/api/reports/analytics/resolution-time/export?${buildParams(from, to, userId)}`, `resolution_time_${from}_${to}.csv`);

export const exportClosingReasons = (from: string, to: string, userId?: number): Promise<void> =>
  downloadCsv(`/api/reports/analytics/closing-reasons/export?${buildParams(from, to, userId)}`, `closing_reasons_${from}_${to}.csv`);

export const exportEmployeeSummary = (from: string, to: string): Promise<void> =>
  downloadCsv(`/api/reports/analytics/employee-summary/export?${buildParams(from, to)}`, `employee_summary_${from}_${to}.csv`);
