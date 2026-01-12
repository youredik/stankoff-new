import {Datagrid, FunctionField, List, NumberField, TextField, TopToolbar} from 'react-admin';
import {ExportButton} from "ra-ui-materialui";
import {Tooltip} from '@mui/material';
import {formatDistanceToNow} from 'date-fns';
import {ru} from 'date-fns/locale';
import {StatusChip} from './common';

const ListActions = () => (
  <TopToolbar>
    <ExportButton/>
  </TopToolbar>
);

export const SupportTicketList = () => (
  <List sort={{field: 'createdAt', order: 'DESC'}} actions={<ListActions/>}>
    <Datagrid
      bulkActionButtons={false}
    >
      <TextField source="subject" label="Цель обращения"/>
      <FunctionField
        label="Статус"
        render={(record: any) => <StatusChip status={record?.currentStatus || ''}
                                             statusValue={record?.currentStatusValue || ''}/>}
      />
      <TextField source="authorName" label="Автор"/>
      <FunctionField
        label="Создано"
        render={(record: any) => (
          <Tooltip title={new Date(record.createdAt).toLocaleString('ru-RU')}>
            <span>{formatDistanceToNow(new Date(record.createdAt), {addSuffix: true, locale: ru})}</span>
          </Tooltip>
        )}
      />
      <NumberField source="orderId" label="ID заказа"/>
    </Datagrid>
  </List>
);
