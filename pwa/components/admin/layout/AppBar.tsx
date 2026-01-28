import {AppBar, TitlePortal, UserMenu} from "react-admin";
import Logout from "./Logout";

const CustomAppBar = () => (
  <AppBar
    userMenu={
      <UserMenu>
        <Logout/>
      </UserMenu>
    }
    sx={(theme) => ({
      backgroundColor: theme.palette.mode === 'dark' ? '#333' : '#32c5d2',
    })}
  >
    <TitlePortal/>
  </AppBar>
);

export default CustomAppBar;
