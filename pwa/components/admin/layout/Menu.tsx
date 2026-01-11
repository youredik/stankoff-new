import {Menu} from "react-admin";
import MenuBookIcon from "@mui/icons-material/MenuBook";
import CommentIcon from "@mui/icons-material/Comment";
import SettingsIcon from '@mui/icons-material/Settings';

const CustomMenu = () => (
  <Menu>
    <Menu.Item
      to="/admin/books"
      primaryText="Books"
      leftIcon={<MenuBookIcon/>}
    />
    <Menu.Item
      to="/admin/reviews"
      primaryText="Reviews"
      leftIcon={<CommentIcon/>}
    />
    <Menu.Item
      to="/support_tickets"
      primaryText="Заявки в ТП"
      leftIcon={<SettingsIcon/>}
    />
  </Menu>
);
export default CustomMenu;
