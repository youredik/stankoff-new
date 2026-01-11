import {Create, SimpleForm, TextInput} from 'react-admin';
import {Box} from '@mui/material';

export const SupportTicketCreate = () => (
  <Create redirect="show">
    <Box sx={{maxWidth: 600}}>
      <SimpleForm>
        <TextInput source="subject" label="Цель обращения" fullWidth/>
        <TextInput source="description" label="Причина обращения" multiline fullWidth rows={4}/>
      </SimpleForm>
    </Box>
  </Create>
);
