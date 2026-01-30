import {Show, SimpleShowLayout, TextField} from 'react-admin';

export const UserShow = () => (
  <Show>
    <SimpleShowLayout>
      <TextField source="id" label="ID"/>
      <TextField source="email" label="Email"/>
      <TextField source="firstName" label="Имя"/>
      <TextField source="lastName" label="Фамилия"/>
      <TextField source="name" label="Имя и фамилия"/>
    </SimpleShowLayout>
  </Show>
);
