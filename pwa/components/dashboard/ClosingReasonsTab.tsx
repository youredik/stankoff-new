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
  exportClosingReasons,
  type ClosingReasonsResponse,
} from '../../services/analyticsService';

const reasonColors: Record<string, 'success' | 'warning' | 'info' | 'default'> = {
  resolved: 'success',
  transferred_to_claims: 'warning',
  transferred_to_service: 'info',
  transferred_to_op: 'default',
};

interface ClosingReasonsTabProps {
  fromDate: string;
  toDate: string;
  userId?: number;
  data: ClosingReasonsResponse | null;
  onDataLoaded: (data: ClosingReasonsResponse) => void;
  loading: boolean;
}

export const ClosingReasonsTab = ({fromDate, toDate, userId, data, onDataLoaded, loading}: ClosingReasonsTabProps) => {
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleExport = useCallback(async () => {
    setExporting(true);
    setError(null);
    try {
      await exportClosingReasons(fromDate, toDate, userId);
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
        {Object.entries(data.reasonLabels).map(([key, label]) => (
          <Chip
            key={key}
            label={`${label}: ${data.counts[key] || 0}`}
            color={reasonColors[key] || 'default'}
            variant="outlined"
          />
        ))}
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
                <TableCell sx={{fontWeight: 600, minWidth: 160}}>Причина закрытия</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {data.rows.map((row) => (
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
                    <Chip
                      label={row.closing_reason_label}
                      color={reasonColors[row.closing_reason] || 'default'}
                      size="small"
                      variant="outlined"
                    />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Box>
  );
};
