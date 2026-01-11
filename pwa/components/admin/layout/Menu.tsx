import {Menu} from "react-admin";
import SettingsIcon from '@mui/icons-material/Settings';

const CustomMenu = () => (
  <Menu>
    <Menu.Item
      to="/support_tickets"
      primaryText="Заявки в ТП"
      leftIcon={<SettingsIcon/>}
    />
  </Menu>
);
export default CustomMenu;
