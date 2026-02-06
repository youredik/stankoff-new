import {authenticatedFetch} from '../utils/authenticatedFetch';

export const getStatuses = async () => {
  const response = await authenticatedFetch('/api/support-tickets/statuses');
  return response.json();
};

export const getClosingReasons = async () => {
  const response = await authenticatedFetch('/api/support-tickets/closing-reasons');
  return response.json();
};

export const changeStatus = async (ticketId: string, data: {
  status: string,
  comment: string,
  closingReason?: string
}) => {
  const response = await authenticatedFetch(`/api/support-tickets/${ticketId}/change-status`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const errorData = await response.json();
    const error = new Error(errorData.error || errorData.message || 'Failed to change status');
    (error as any).existingTicket = errorData.existingTicket;
    throw error;
  }

  return response.json();
};

export const assignUser = async (ticketId: string, userId: number) => {
  const response = await authenticatedFetch(`/api/support-tickets/${ticketId}/assign-user`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({userId}),
  });

  if (!response.ok) {
    const errorData = await response.json();
    throw new Error(errorData.error || errorData.message || 'Failed to assign user');
  }

  return response.json();
};
