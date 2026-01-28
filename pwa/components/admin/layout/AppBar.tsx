import {AppBar, TitlePortal, UserMenu} from "react-admin";
import Logout from "./Logout";

const CustomAppBar = () => (
  <AppBar
    userMenu={
      <UserMenu>
        <Logout/>
      </UserMenu>
    }
    sx={{
      backgroundColor: '#32c5d2',
    }}
  >
    <TitlePortal/>
  </AppBar>
);

export default CustomAppBar;
