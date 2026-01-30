import {Menu} from "react-admin";
import SettingsIcon from '@mui/icons-material/Settings';
import PeopleAltIcon from '@mui/icons-material/PeopleAlt';

const CustomMenu = () => (
  <Menu>
    <Menu.Item
      to="/support_tickets"
      primaryText="Техническая поддержка"
      leftIcon={<SettingsIcon/>}
    />
    <Menu.Item
      to="/users"
      primaryText="Пользователи"
      leftIcon={<PeopleAltIcon/>}
    />
  </Menu>
);
export default CustomMenu;
