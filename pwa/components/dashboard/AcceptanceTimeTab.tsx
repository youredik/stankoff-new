import React, {useState, useCallback} from 'react';
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
  Tooltip,
  Typography,
} from '@mui/material';
import DownloadIcon from '@mui/icons-material/Download';
import {
  getAcceptanceTime,
  exportAcceptanceTime,
  type AcceptanceTimeResponse,
} from '../../services/analyticsService';

const SLA_MINUTES = 120;

const formatDuration = (minutes: number): string => {
  const totalMinutes = Math.round(minutes);
  const days = Math.floor(totalMinutes / (60 * 24));
  const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
  const mins = totalMinutes % 60;

  let result = '';
  if (days > 0) result += `${days} д. `;
  if (hours > 0) result += `${hours} ч. `;
  if (mins > 0 || result === '') result += `${mins} мин.`;
  return result.trim();
};

interface AcceptanceTimeTabProps {
  fromDate: string;
  toDate: string;
  userId?: number;
  data: AcceptanceTimeResponse | null;
  onDataLoaded: (data: AcceptanceTimeResponse) => void;
  loading: boolean;
}

export const AcceptanceTimeTab = ({fromDate, toDate, userId, data, onDataLoaded, loading}: AcceptanceTimeTabProps) => {
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleExport = useCallback(async () => {
    setExporting(true);
    setError(null);
    try {
      await exportAcceptanceTime(fromDate, toDate, userId);
    } catch (err: any) {
      setError(err.message || 'Не удалось скачать файл');
    } finally {
      setExporting(false);
    }
  }, [fromDate, toDate, userId]);

  if (loading) {
    return (
      <Box sx={{display: 'flex', justifyContent: 'center', py: 6}}>
        <CircularProgress />
      </Box>
    );
  }

  if (!data) {
    return <Typography color="text.secondary">Нажмите «Сформировать» для загрузки данных</Typography>;
  }

  return (
    <Box>
      {error && (
        <Alert severity="error" sx={{mb: 2}} onClose={() => setError(null)}>
          {error}
        </Alert>
      )}

      <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, mb: 2, flexWrap: 'wrap'}}>
        <Chip label={`Всего: ${data.total}`} variant="outlined" />
        <Chip label={`В нормативе (≤2ч): ${data.withinSla}`} color="success" variant="outlined" />
        <Chip label={`Просрочено (>2ч): ${data.overdue}`} color="error" variant="outlined" />
        {data.rows.length > 0 && (
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

      {data.rows.length === 0 ? (
        <Typography color="text.secondary">Нет данных за выбранный период</Typography>
      ) : (
        <TableContainer sx={{maxHeight: 'calc(100vh - 350px)'}}>
          <Table stickyHeader size="small">
            <TableHead>
              <TableRow>
                <TableCell sx={{fontWeight: 600, minWidth: 80}}>#</TableCell>
                <TableCell sx={{fontWeight: 600, minWidth: 200}}>Название</TableCell>
                <TableCell sx={{fontWeight: 600, minWidth: 160}}>Контрагент</TableCell>
                <TableCell sx={{fontWeight: 600, minWidth: 140}}>Ответственный</TableCell>
                <TableCell sx={{fontWeight: 600, minWidth: 140}}>Время принятия</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {data.rows.map((row) => {
                const minutes = Number(row.acceptance_time_minutes);
                const isOverdue = minutes > SLA_MINUTES;
                return (
                  <TableRow key={row.id} hover>
                    <TableCell>{row.id}</TableCell>
                    <TableCell>
                      <Tooltip title={row.subject}>
                        <span style={{display: 'block', maxWidth: 280, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis'}}>
                          {row.subject}
                        </span>
                      </Tooltip>
                    </TableCell>
                    <TableCell>{row.contractor || ''}</TableCell>
                    <TableCell>{row.user_name || ''}</TableCell>
                    <TableCell>
                      <Box component="span" sx={{color: isOverdue ? '#f44336' : '#4caf50', fontWeight: 600}}>
                        {formatDuration(minutes)}
                      </Box>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Box>
  );
};
