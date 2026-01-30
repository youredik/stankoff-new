import {Datagrid, List, TextField} from 'react-admin';

export const UserList = () => (
  <List>
    <Datagrid rowClick="show">
      <TextField source="id" label="ID"/>
      <TextField source="email" label="Email"/>
      <TextField source="firstName" label="Имя"/>
      <TextField source="lastName" label="Фамилия"/>
      <TextField source="name" label="Имя и фамилия"/>
    </Datagrid>
  </List>
);
