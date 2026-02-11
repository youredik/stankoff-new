import {Box, Button, TextField, ToggleButton, ToggleButtonGroup} from '@mui/material';
import SearchIcon from '@mui/icons-material/Search';
import {format, startOfMonth, startOfWeek} from 'date-fns';

interface DateRangePickerProps {
  fromDate: string;
  toDate: string;
  onFromChange: (value: string) => void;
  onToChange: (value: string) => void;
  onSearch: () => void;
  loading: boolean;
}

type Preset = 'today' | 'week' | 'month';

const getPresetDates = (preset: Preset): {from: string; to: string} => {
  const today = new Date();
  const to = format(today, 'yyyy-MM-dd');

  switch (preset) {
    case 'today':
      return {from: to, to};
    case 'week':
      return {from: format(startOfWeek(today, {weekStartsOn: 1}), 'yyyy-MM-dd'), to};
    case 'month':
      return {from: format(startOfMonth(today), 'yyyy-MM-dd'), to};
  }
};

export const DateRangePicker = ({fromDate, toDate, onFromChange, onToChange, onSearch, loading}: DateRangePickerProps) => {
  const handlePreset = (_: React.MouseEvent<HTMLElement>, value: Preset | null) => {
    if (!value) return;
    const dates = getPresetDates(value);
    onFromChange(dates.from);
    onToChange(dates.to);
  };

  const currentPreset = (): Preset | null => {
    const today = format(new Date(), 'yyyy-MM-dd');
    if (fromDate === today && toDate === today) return 'today';
    if (fromDate === format(startOfWeek(new Date(), {weekStartsOn: 1}), 'yyyy-MM-dd') && toDate === today) return 'week';
    if (fromDate === format(startOfMonth(new Date()), 'yyyy-MM-dd') && toDate === today) return 'month';
    return null;
  };

  return (
    <Box sx={{display: 'flex', alignItems: 'center', gap: 2, flexWrap: 'wrap'}}>
      <TextField
        label="Дата от"
        type="date"
        value={fromDate}
        onChange={(e) => onFromChange(e.target.value)}
        InputLabelProps={{shrink: true}}
        size="small"
        sx={{width: 170}}
      />
      <TextField
        label="Дата до"
        type="date"
        value={toDate}
        onChange={(e) => onToChange(e.target.value)}
        InputLabelProps={{shrink: true}}
        size="small"
        sx={{width: 170}}
      />
      <Button
        variant="contained"
        startIcon={<SearchIcon />}
        onClick={onSearch}
        disabled={loading || !fromDate || !toDate}
      >
        Сформировать
      </Button>
      <ToggleButtonGroup
        value={currentPreset()}
        exclusive
        onChange={handlePreset}
        size="small"
      >
        <ToggleButton value="today">Сегодня</ToggleButton>
        <ToggleButton value="week">Неделя</ToggleButton>
        <ToggleButton value="month">Месяц</ToggleButton>
      </ToggleButtonGroup>
    </Box>
  );
};
