import {AppBar, TitlePortal, UserMenu} from "react-admin";
import Logout from "./Logout";

const CustomAppBar = () => (
  <AppBar
    userMenu={
      <UserMenu>
        <Logout/>
      </UserMenu>
    }
  >
    <TitlePortal/>
  </AppBar>
);

export default CustomAppBar;
