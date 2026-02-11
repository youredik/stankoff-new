import {authenticatedFetch} from '../utils/authenticatedFetch';

export interface NpsReportRow {
  id: number;
  subject: string;
  author_name: string;
  contractor: string | null;
  order_id: number | null;
  user_name: string | null;
  created_at: string;
  closed_at: string;
  handling_time_minutes: number;
  closing_comment: string | null;
}

export interface NpsReportResponse {
  from: string;
  to: string;
  total: number;
  rows: NpsReportRow[];
}

export const getNpsReport = async (from: string, to: string): Promise<NpsReportResponse> => {
  const params = new URLSearchParams({from, to});
  const response = await authenticatedFetch(`/api/reports/nps?${params}`);
  if (!response.ok) {
    throw new Error('Не удалось загрузить отчет');
  }
  return response.json();
};

export const exportNpsReport = async (from: string, to: string): Promise<void> => {
  const params = new URLSearchParams({from, to});
  const response = await authenticatedFetch(`/api/reports/nps/export?${params}`);
  if (!response.ok) {
    throw new Error('Не удалось выгрузить отчет');
  }
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `nps_report_${from}_${to}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(url);
};
