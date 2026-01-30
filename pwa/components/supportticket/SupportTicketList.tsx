import {
  Datagrid,
  DateInput,
  Filter,
  FunctionField,
  List,
  NumberInput,
  SelectInput,
  TextField,
  TextInput,
  TopToolbar
} from 'react-admin';
import {ExportButton} from "ra-ui-materialui";
import {Box, Tooltip, Typography} from '@mui/material';
import {formatDistanceToNow} from 'date-fns';
import {ru} from 'date-fns/locale';
import {StatusChip} from './common';
import {getStatuses} from '../../services/supportTicketService';
import {useEffect, useState} from 'react';

const ListActions = () => (
  <TopToolbar>
    <ExportButton/>
  </TopToolbar>
);

const TicketFilters = () => {
  const [statuses, setStatuses] = useState([]);
  const [users, setUsers] = useState([]);

  useEffect(() => {
    const loadData = async () => {
      try {
        const [statusesResponse, usersResponse] = await Promise.all([
          getStatuses(),
          fetch('/api/support-tickets/assignable-users').then(res => res.json())
        ]);
        setStatuses(statusesResponse);
        setUsers(usersResponse);
      } catch (error) {
        console.error('Failed to load filter data:', error);
      }
    };
    loadData();
  }, []);

  return (
    <Filter>
      <NumberInput label="# заявки" source="id" alwaysOn sx={{maxWidth: 100}}/>
      <TextInput label="Контрагент" source="contractor" alwaysOn sx={{maxWidth: 150}}/>
      <SelectInput label="Ответственный" source="user" choices={users} optionText="name" optionValue="id" alwaysOn/>
      <SelectInput label="Статус" source="status" choices={statuses} alwaysOn/>
      <DateInput label="Дата создания" source="createdAt" alwaysOn/>
      <DateInput label="Дата закрытия" source="closedAt" alwaysOn/>
      <NumberInput label="# заказа" source="orderId" alwaysOn sx={{maxWidth: 100}}/>
    </Filter>
  );
};

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
                                             statusValue={record?.status || ''}/>}
        sortBy="status"
        sortable
      />
      <TextField source="contractor" label="Контрагент"/>
      <TextField source="subject" label="Тема обращения"/>
      <TextField source="userName" label="Ответственный"/>
      <TextField source="authorName" label="Автор"/>
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
      <FunctionField
        label="Дата закрытия"
        render={(record: any) => record.closedAt ? (
          <Tooltip title={new Date(record.closedAt).toLocaleString('ru-RU')}>
            <span>{formatDistanceToNow(new Date(record.closedAt), {addSuffix: true, locale: ru})}</span>
          </Tooltip>
        ) : ''}
        sortBy="closedAt"
        sortable
      />
      <FunctionField
        label="# заказа"
        render={(record: any) => record.orderId ? (
          <a
            href={`https://workspace.stankoff.ru/commerce/order/view/${record.orderId}`}
            target="_blank"
            rel="noopener noreferrer"
            onClick={(e) => e.stopPropagation()}
          >
            {record.orderId}
          </a>
        ) : ''}
        sortBy="orderId"
        sortable
      />
    </Datagrid>
  </List>
);
