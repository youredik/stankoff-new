
import {Box, CircularProgress, Typography} from '@mui/material';
import { useEffect, useState } from 'react';
import { getSession } from 'next-auth/react';

export const OrderInfo = ({ orderId, orderData }: { orderId: string, orderData: any }) => {
  const [orderInfo, setOrderInfo] = useState<any>(null);
  const [orderLoading, setOrderLoading] = useState(false);
  const [orderError, setOrderError] = useState(false);

  useEffect(() => {
    const fetchOrderInfo = async () => {
      if (!orderId) return;

      setOrderLoading(true);
      setOrderError(false);
      try {
        const session = await getSession();
        const response = await fetch(`/api/orders/${orderId}`, {
          headers: {
            Authorization: `Bearer ${(session as any)?.accessToken}`,
          },
        });
        const data = await response.json();
        if (data.order) {
          setOrderInfo(data.order);
        } else if (data.error) {
          // Backend returned an error, treat as fetch failure
          throw new Error(data.message || data.error);
        }
      } catch (err) {
        console.error('Failed to fetch order data', err);
        setOrderError(true);
      } finally {
        setOrderLoading(false);
      }
    };

    fetchOrderInfo();
  }, [orderId]);

  if (orderLoading) {
    return <CircularProgress size={20} />;
  }

  if (orderError) {
    return <span style={{ color: 'red' }}>Ошибка загрузки данных</span>;
  }

  if (!orderInfo) {
    return null;
  }

  const orderItems = orderInfo?.items || [];

  const FieldRow = ({label, children}: {label: string; children: React.ReactNode}) => (
    <Box
      sx={{
        display: 'grid',
        gridTemplateColumns: {xs: '1fr', sm: '160px 1fr'},
        gap: 1,
        alignItems: 'start',
        mb: 1.5,
        '&:last-of-type': { mb: 0 },
      }}
    >
      <Typography variant="body2" color="text.secondary">
        {label}
      </Typography>
      <Box>{children}</Box>
    </Box>
  );

  return (
    <Box>
      <Box sx={{ mb: 2 }}>
        <FieldRow label="Номер заказа">
          <a
            href={`https://workspace.stankoff.ru/commerce/order/view/${orderInfo.id}`}
            target="_blank"
            rel="noopener noreferrer"
            style={{ color: '#1976d2', textDecoration: 'underline' }}
          >
            #{orderInfo.id}
          </a>
        </FieldRow>
        <FieldRow label="Ответственный">
          <Typography variant="body1">{orderInfo.manager || '—'}</Typography>
        </FieldRow>
        <FieldRow label="Контрагент">
          <Typography variant="body1">{orderInfo.counterparty_name || '—'}</Typography>
        </FieldRow>
        {orderInfo.counterparty_inn && orderInfo.counterparty_inn !== "0" && (
          <FieldRow label="ИНН">
            <Typography variant="body1">{orderInfo.counterparty_inn}</Typography>
          </FieldRow>
        )}
        {orderInfo.counterparty_kpp && orderInfo.counterparty_kpp !== "0" && (
          <FieldRow label="КПП">
            <Typography variant="body1">{orderInfo.counterparty_kpp}</Typography>
          </FieldRow>
        )}
        {orderData.contactName && (
          <FieldRow label="Контактное лицо">
            <Typography variant="body1">{orderData.contactName}</Typography>
          </FieldRow>
        )}
        {orderData.contactPhone && (
          <FieldRow label="Телефон">
            <Typography variant="body1">{orderData.contactPhone}</Typography>
          </FieldRow>
        )}
        {orderData.contactEmail && (
          <FieldRow label="Email">
            <Typography variant="body1">{orderData.contactEmail}</Typography>
          </FieldRow>
        )}
      </Box>

      <Typography variant="subtitle2" sx={{fontWeight: 600, mb: 1}}>
        Выбранные товары
      </Typography>
      {orderData.selectedItems.map((id: string) => {
        const itemId = parseInt(id.split('_')[0]);
        const item = orderItems.find((i: any) => i.id == itemId);
        return item ? (
          <Box key={id} sx={{ ml: 2, mb: 1 }}>
            <Typography variant="body2">
              {item.name} (Кол-во: {parseInt(item.quantity)}, Сумма: {item.sum})
            </Typography>
          </Box>
        ) : (
          <Box key={id} sx={{ ml: 2 }}>
            <Typography variant="body2" color="text.secondary">
              Товар ID {id} не найден
            </Typography>
          </Box>
        );
      })}
    </Box>
  );
};
