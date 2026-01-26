import {Menu} from "react-admin";
import SettingsIcon from '@mui/icons-material/Settings';

const CustomMenu = () => (
  <Menu>
    <Menu.Item
      to="/support_tickets"
      primaryText="Техническая поддержка"
      leftIcon={<SettingsIcon/>}
    />
  </Menu>
);
export default CustomMenu;
