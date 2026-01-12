
import { Box, CircularProgress } from '@mui/material';
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

  return (
    <Box>
      <Box sx={{ mb: 2 }}>
        <strong>Номер заказа:</strong> #{orderInfo.id}<br />
        <strong>Ответственный менеджер:</strong> {orderInfo.manager}<br />
        <strong>Контрагент:</strong> {orderInfo.counterparty_name}<br />
        {orderInfo.counterparty_inn && orderInfo.counterparty_inn !== "0" && (
          <>
            <strong>ИНН:</strong> {orderInfo.counterparty_inn}<br />
          </>
        )}
        {orderInfo.counterparty_kpp && orderInfo.counterparty_kpp !== "0" && (
          <>
            <strong>КПП:</strong> {orderInfo.counterparty_kpp}<br />
          </>
        )}
        {orderData.contactName && (
          <>
            <strong>Контактное лицо:</strong> {orderData.contactName}<br />
          </>
        )}
        {orderData.contactPhone && (
          <>
            <strong>Телефон:</strong> {orderData.contactPhone}<br />
          </>
        )}
        {orderData.contactEmail && (
          <>
            <strong>Email:</strong> {orderData.contactEmail}<br />
          </>
        )}
      </Box>
      <strong>Выбранные товары:</strong>
      {orderData.selectedItems.map((id: string) => {
        const itemId = parseInt(id.split('_')[0]);
        const item = orderItems.find((i: any) => i.id == itemId);
        return item ? (
          <Box key={id} sx={{ ml: 2, mb: 1 }}>
            {item.name} (Кол-во: {parseInt(item.quantity)}, Сумма: {item.sum})
          </Box>
        ) : (
          <Box key={id} sx={{ ml: 2 }}>Товар ID {id} не найден</Box>
        );
      })}
    </Box>
  );
};
