import React, {useState, useEffect} from 'react';
import {useNotify, useShowContext} from 'react-admin';
import {Box, Button, FormControl, InputLabel, MenuItem, Select, Typography} from '@mui/material';
import {assignUser} from '../../services/supportTicketService';

export const UserAssignment = () => {
  const {record, refetch} = useShowContext();
  const notify = useNotify();
  const [users, setUsers] = useState<any[]>([]);
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const loadUsers = async () => {
      try {
        const response = await fetch('/api/support-tickets/assignable-users');
        if (response.ok) {
          const data = await response.json();
          setUsers(data);
        }
      } catch (error) {
        console.error('Failed to load users:', error);
      }
    };
    loadUsers();
  }, []);

  const handleAssign = async () => {
    if (!selectedUserId || !record?.id) return;

    setLoading(true);
    try {
      const ticketId = record.id.split('/').pop();
      await assignUser(ticketId, selectedUserId);

      notify('Ответственный успешно изменен', {type: 'success'});
      refetch();
      setSelectedUserId(null);
    } catch (err: any) {
      notify(err.message, {type: 'error'});
    } finally {
      setLoading(false);
    }
  };

  if (!record) return null;

  return (
    <Box sx={{mt: 2, mb: 2}}>
      <Typography variant="h6" gutterBottom>
        Изменить ответственного
      </Typography>
      <FormControl fullWidth sx={{mb: 2}}>
        <InputLabel>Выберите пользователя</InputLabel>
        <Select
          value={selectedUserId || ''}
          onChange={(e) => setSelectedUserId(Number(e.target.value))}
          label="Выберите пользователя"
        >
          {users.map((user) => (
            <MenuItem key={user.id} value={user.id}>
              {user.name}
            </MenuItem>
          ))}
        </Select>
      </FormControl>
      <Button
        variant="contained"
        onClick={handleAssign}
        disabled={!selectedUserId || loading}
      >
        {loading ? 'Назначение...' : 'Назначить'}
      </Button>
    </Box>
  );
};
