import React, {useState, useCallback, useEffect} from 'react';
import {
  Alert,
  Box,
  Button,
  Chip,
  CircularProgress,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import DownloadIcon from '@mui/icons-material/Download';
import SearchIcon from '@mui/icons-material/Search';
import {format, startOfMonth} from 'date-fns';
import {getSession} from 'next-auth/react';
import {type Session} from '../../app/auth';
import {
  getNpsReport,
  exportNpsReport,
  type NpsReportRow,
  type NpsReportResponse,
} from '../../services/npsReportService';

const formatHandlingTime = (minutes: number): string => {
  const totalMinutes = Math.round(minutes);
  const days = Math.floor(totalMinutes / (60 * 24));
  const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
  const mins = totalMinutes % 60;

  let result = '';
  if (days > 0) result += `${days} д. `;
  if (hours > 0) result += `${hours} ч. `;
  if (mins > 0) result += `${mins} мин.`;
  if (result === '') result = '0 мин.';
  return result.trim();
};

const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr);
  return date.toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

export const NpsReport = () => {
  const [fromDate, setFromDate] = useState(format(startOfMonth(new Date()), 'yyyy-MM-dd'));
  const [toDate, setToDate] = useState(format(new Date(), 'yyyy-MM-dd'));
  const [report, setReport] = useState<NpsReportResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [hasAccess, setHasAccess] = useState<boolean | null>(null);

  useEffect(() => {
    const checkRole = async () => {
      const session = await getSession() as Session | null;
      const roles = session?.user?.roles || [];
      setHasAccess(roles.some(role => {
        const r = role.toLowerCase();
        return r === 'support_manager' || r === 'admin';
      }));
    };
    checkRole();
  }, []);

  const handleSearch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await getNpsReport(fromDate, toDate);
      setReport(data);
    } catch (err: any) {
      setError(err.message || 'Произошла ошибка');
      setReport(null);
    } finally {
      setLoading(false);
    }
  }, [fromDate, toDate]);

  const handleExport = useCallback(async () => {
    setExporting(true);
    setError(null);
    try {
      await exportNpsReport(fromDate, toDate);
    } catch (err: any) {
      setError(err.message || 'Не удалось скачать файл');
    } finally {
      setExporting(false);
    }
  }, [fromDate, toDate]);

  if (hasAccess === null) {
    return (
      <Box sx={{display: 'flex', justifyContent: 'center', py: 6}}>
        <CircularProgress />
      </Box>
    );
  }

  if (!hasAccess) {
    return (
      <Box sx={{p: 2}}>
        <Alert severity="warning">У вас нет доступа к этому разделу</Alert>
      </Box>
    );
  }

  return (
    <Box sx={{p: 2}}>
      <Typography variant="h5" sx={{mb: 3}}>Отчет NPS</Typography>

      <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 3, flexWrap: 'wrap'}}>
        <TextField
          label="Дата от"
          type="date"
          value={fromDate}
          onChange={(e) => setFromDate(e.target.value)}
          InputLabelProps={{shrink: true}}
          size="small"
          sx={{width: 170}}
        />
        <TextField
          label="Дата до"
          type="date"
          value={toDate}
          onChange={(e) => setToDate(e.target.value)}
          InputLabelProps={{shrink: true}}
          size="small"
          sx={{width: 170}}
        />
        <Button
          variant="contained"
          startIcon={<SearchIcon />}
          onClick={handleSearch}
          disabled={loading || !fromDate || !toDate}
        >
          Сформировать
        </Button>
        {report && report.rows.length > 0 && (
          <Button
            variant="outlined"
            startIcon={exporting ? <CircularProgress size={18} /> : <DownloadIcon />}
            onClick={handleExport}
            disabled={exporting}
          >
            Скачать CSV
          </Button>
        )}
      </Box>

      {error && (
        <Alert severity="error" sx={{mb: 2}} onClose={() => setError(null)}>
          {error}
        </Alert>
      )}

      {loading ? (
        <Box sx={{display: 'flex', justifyContent: 'center', py: 6}}>
          <CircularProgress />
        </Box>
      ) : report ? (
        <>
          <Box sx={{mb: 2}}>
            <Chip
              label={`Всего заявок: ${report.total}`}
              color="success"
              variant="outlined"
            />
          </Box>

          {report.rows.length === 0 ? (
            <Typography color="text.secondary">
              Нет завершённых заявок с причиной «Решено» за выбранный период
            </Typography>
          ) : (
            <TableContainer sx={{maxHeight: 'calc(100vh - 300px)'}}>
              <Table stickyHeader size="small">
                <TableHead>
                  <TableRow>
                    <TableCell sx={{fontWeight: 600, minWidth: 80}}># заявки</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 200}}>Тема обращения</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 140}}>Автор</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 160}}>Контрагент</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 80}}># заказа</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 140}}>Ответственный</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 140}}>Дата создания</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 140}}>Дата закрытия</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 120}}>Закрыто за</TableCell>
                    <TableCell sx={{fontWeight: 600, minWidth: 200}}>Комментарий</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {report.rows.map((row: NpsReportRow) => (
                    <TableRow key={row.id} hover>
                      <TableCell>{row.id}</TableCell>
                      <TableCell>
                        <Tooltip title={row.subject}>
                          <span style={{
                            display: 'block',
                            maxWidth: 280,
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                          }}>
                            {row.subject}
                          </span>
                        </Tooltip>
                      </TableCell>
                      <TableCell>{row.author_name}</TableCell>
                      <TableCell>{row.contractor || ''}</TableCell>
                      <TableCell>
                        {row.order_id ? (
                          <a
                            href={`https://workspace.stankoff.ru/commerce/order/view/${row.order_id}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            style={{color: '#1976d2', textDecoration: 'underline'}}
                          >
                            {row.order_id}
                          </a>
                        ) : ''}
                      </TableCell>
                      <TableCell>{row.user_name || ''}</TableCell>
                      <TableCell>{formatDate(row.created_at)}</TableCell>
                      <TableCell>{formatDate(row.closed_at)}</TableCell>
                      <TableCell>{formatHandlingTime(row.handling_time_minutes)}</TableCell>
                      <TableCell>
                        {row.closing_comment ? (
                          <Tooltip title={row.closing_comment}>
                            <span style={{
                              display: 'block',
                              maxWidth: 240,
                              whiteSpace: 'nowrap',
                              overflow: 'hidden',
                              textOverflow: 'ellipsis',
                            }}>
                              {row.closing_comment}
                            </span>
                          </Tooltip>
                        ) : ''}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}
        </>
      ) : null}
    </Box>
  );
};
