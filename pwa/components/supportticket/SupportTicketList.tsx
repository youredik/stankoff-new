import {
  Datagrid,
  DateInput,
  FilterForm,
  FunctionField,
  ListBase,
  NumberInput,
  Pagination,
  SelectInput,
  TextField,
  TextInput,
  useGetList,
  useListContext
} from 'react-admin';
import {Alert, Box, Chip, Tooltip, Typography} from '@mui/material';
import {formatDistanceToNow} from 'date-fns';
import {ru} from 'date-fns/locale';
import {StatusChip} from './common';
import {getStatuses} from '../../services/supportTicketService';
import {useEffect, useState} from 'react';

const Empty = () => (
  <Box textAlign="center" m={1}>
    <Typography variant="h4" paragraph>
      Заявок пока нет
    </Typography>
  </Box>
);

const StatusSummary = () => {
  const {total} = useListContext();
  const commonParams = {pagination: {page: 1, perPage: 1}, sort: {field: 'id', order: 'DESC' as const}};
  const {total: newTotal, isLoading: newLoading} = useGetList('support_tickets', {
    ...commonParams,
    filter: {status: 'new'}
  });
  const {total: inProgressTotal, isLoading: inProgressLoading} = useGetList('support_tickets', {
    ...commonParams,
    filter: {status: 'in_progress'}
  });
  const {total: postponedTotal, isLoading: postponedLoading} = useGetList('support_tickets', {
    ...commonParams,
    filter: {status: 'postponed'}
  });
  const {total: completedTotal, isLoading: completedLoading} = useGetList('support_tickets', {
    ...commonParams,
    filter: {status: 'completed'}
  });

  return (
    <Box sx={{display: 'flex', gap: 1, flexWrap: 'wrap'}}>
      <Chip label={`Все: ${total ?? '—'}`}/>
      <Chip label={`Новые: ${newLoading ? '—' : newTotal ?? 0}`}/>
      <Chip label={`В работе: ${inProgressLoading ? '—' : inProgressTotal ?? 0}`}/>
      <Chip label={`Отложено: ${postponedLoading ? '—' : postponedTotal ?? 0}`}/>
      <Chip label={`Завершено: ${completedLoading ? '—' : completedTotal ?? 0}`}/>
    </Box>
  );
};

const isEmptyValue = (value: unknown): boolean => {
  if (value === '' || value == null) return true;
  if (Array.isArray(value)) return value.length === 0;
  if (typeof value === 'object') {
    return Object.values(value).every((entry) => isEmptyValue(entry));
  }
  return false;
};

const countActiveFilters = (values: Record<string, unknown>) =>
  Object.entries(values).reduce((count, [, value]) => (
    isEmptyValue(value) ? count : count + 1
  ), 0);

const SupportTicketListView = () => {
  const [statuses, setStatuses] = useState([]);
  const [users, setUsers] = useState([]);
  const {isLoading, total, error, filterValues} = useListContext();

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

  const filters = [
    <NumberInput key="id" label="# заявки" source="id" alwaysOn sx={{maxWidth: 100}}/>,
    <TextInput key="contractor" label="Контрагент" source="contractor" alwaysOn sx={{maxWidth: 150}}/>,
    <SelectInput key="user" label="Ответственный" source="user" choices={users} optionText="name" optionValue="id"
                 alwaysOn/>,
    <SelectInput key="status" label="Статус" source="status" choices={statuses} alwaysOn sx={{maxWidth: 220}}/>,
    <DateInput key="createdAt" label="Дата создания" source="createdAt" alwaysOn/>,
    <DateInput key="closedAt" label="Дата закрытия" source="closedAt" alwaysOn/>,
    <NumberInput key="orderId" label="# заказа" source="orderId" alwaysOn sx={{maxWidth: 100}}/>,
  ];

  return (
    <Box>
      <StatusSummary/>
      <Box
        sx={{
          '& .RaFilterForm-root': {
            display: 'grid',
            gap: 16,
            gridTemplateColumns: {
              xs: '1fr',
              sm: '1fr 1fr',
              md: 'repeat(4, minmax(160px, 1fr))'
            },
            alignItems: 'end',
          },
          '& .RaFilterForm-filterFormInput': {
            margin: 0,
            minWidth: 0,
          },
        }}
      >
        <FilterForm filters={filters}/>
      </Box>
      {error ? (
        <Alert severity="error" sx={{mb: 2}}>
          Не удалось загрузить список заявок. Попробуйте обновить страницу.
        </Alert>
      ) : null}
      {!isLoading && (total ?? 0) === 0 ? (
        <Empty/>
      ) : (
        <>
          <Datagrid
            bulkActionButtons={false}
            sx={{
              tableLayout: 'fixed',
              '& .RaDatagrid-headerCell': {
                position: 'sticky',
                top: 0,
                zIndex: 1,
                backgroundColor: 'background.paper'
              },
              '& .MuiTableCell-root': {
                overflow: 'hidden'
              },
              '& .column-id': {width: 110},
              '& .column-status': {width: 140},
              '& .column-contractor': {minWidth: 200},
              '& .column-subject': {minWidth: 240, maxWidth: 360},
              '& .column-userName': {minWidth: 160},
              '& .column-authorName': {minWidth: 160},
              '& .column-createdAt': {minWidth: 160},
              '& .column-closedAt': {minWidth: 160},
              '& .column-orderId': {width: 120},
            }}
          >
            <FunctionField
              label="Номер заявки"
              source="id"
              render={(record: any) => record?.id?.split('/').pop() || ''}
              sortBy="id"
              sortable
            />

            <FunctionField
              label="Статус"
              source="status"
              render={(record: any) => <StatusChip status={record?.currentStatus || ''}
                                                   statusValue={record?.status || ''}/>}
              sortBy="status"
              sortable
            />
            <TextField
              source="contractor"
              label="Контрагент"
              sortable={false}
              sx={{whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis'}}
            />
            <FunctionField
              source="subject"
              label="Тема обращения"
              sortable={false}
              render={(record: any) => record?.subject ? (
                <Tooltip title={record.subject}>
                  <span style={{display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis'}}>
                    {record.subject}
                  </span>
                </Tooltip>
              ) : ''}
            />
            <TextField source="userName" label="Ответственный" sortable={false}/>
            <TextField source="authorName" label="Автор" sortable={false}/>
            <FunctionField
              label="Дата создания"
              source="createdAt"
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
              source="closedAt"
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
              source="orderId"
              sortable={false}
              render={(record: any) => record.orderId ? (
                <a
                  href={`https://workspace.stankoff.ru/commerce/order/view/${record.orderId}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={(e) => e.stopPropagation()}
                  style={{color: '#1976d2', textDecoration: 'underline'}}
                >
                  {record.orderId}
                </a>
              ) : ''}
            />
          </Datagrid>
          <Pagination/>
        </>
      )}
    </Box>
  );
};

export const SupportTicketList = () => (
  <ListBase sort={{field: 'createdAt', order: 'DESC'}} perPage={15}>
    <SupportTicketListView/>
  </ListBase>
);
