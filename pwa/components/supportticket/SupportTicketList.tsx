import {Datagrid, Filter, FunctionField, List, NumberField, NumberInput, TextField, TopToolbar} from 'react-admin';
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
      <FunctionField
        label="Номер заявки"
        render={(record: any) => record?.id?.split('/').pop() || ''}
        sortBy="id"
        sortable
      />

      <FunctionField
        label="Статус"
        render={(record: any) => <StatusChip status={record?.currentStatus || ''}
                                             statusValue={record?.currentStatusValue || ''}/>}
        sortBy="currentStatusValue"
        sortable
      />
      <TextField source="subject" label="Тема обращения" sortable/>
      {/*<TextField source="currentClosingReason" label="Причина закрытия" sortable={false}/>*/}
      <TextField source="userName" label="Исполнитель" sortBy="user.name" sortable/>
      <TextField source="authorName" label="Автор" sortable/>
      <FunctionField
        label="Дата создания"
        render={(record: any) => (
          <Tooltip title={new Date(record.createdAt).toLocaleString('ru-RU')}>
            <span>{formatDistanceToNow(new Date(record.createdAt), {addSuffix: true, locale: ru})}</span>
          </Tooltip>
        )}
        sortBy="createdAt"
        sortable
      />
      {/*<NumberField source="orderId" label="ID заказа" sortable={false}/>*/}
    </Datagrid>
  </List>
);
