import {Datagrid, FunctionField, List, NumberField, TextField, TopToolbar, NumberInput, Filter} from 'react-admin';
import {ExportButton} from "ra-ui-materialui";
import {Box, Tooltip, Typography} from '@mui/material';
import {formatDistanceToNow} from 'date-fns';
import {ru} from 'date-fns/locale';
import {StatusChip} from './common';

const ListActions = () => (
  <TopToolbar>
    <ExportButton/>
  </TopToolbar>
);

const TicketFilters = () => (
  <Filter>
    <NumberInput label="ID заказа" source="orderId" alwaysOn/>
  </Filter>
);

const Empty = () => (
  <Box textAlign="center" m={1}>
    <Typography variant="h4" paragraph>
      Заявок пока нет
    </Typography>
  </Box>
);

export const SupportTicketList = () => (
  <List sort={{field: 'createdAt', order: 'DESC'}} actions={<ListActions/>} filters={<TicketFilters/>} empty={<Empty/>}>
    <Datagrid
      bulkActionButtons={false}
    >
      <TextField source="subject" label="Цель обращения"/>
      <FunctionField
        label="Статус"
        render={(record: any) => <StatusChip status={record?.currentStatus || ''}
                                             statusValue={record?.currentStatusValue || ''}/>}
      />
      <TextField source="currentClosingReason" label="Причина закрытия"/>
      <FunctionField
        label="Создано"
        render={(record: any) => (
          <Tooltip title={new Date(record.createdAt).toLocaleString('ru-RU')}>
            <span>{formatDistanceToNow(new Date(record.createdAt), {addSuffix: true, locale: ru})}</span>
          </Tooltip>
        )}
      />
      <TextField source="userName" label="Ответственный"/>
      <TextField source="authorName" label="Автор"/>
      <NumberField source="orderId" label="ID заказа"/>
    </Datagrid>
  </List>
);
