import {useEffect, useState} from 'react';
import {Menu} from "react-admin";
import DashboardIcon from '@mui/icons-material/Dashboard';
import SettingsIcon from '@mui/icons-material/Settings';
import PeopleAltIcon from '@mui/icons-material/PeopleAlt';
import AssessmentIcon from '@mui/icons-material/Assessment';
import {getSession} from 'next-auth/react';
import {type Session} from '../../../app/auth';

const CustomMenu = () => {
  const [isManager, setIsManager] = useState(false);

  useEffect(() => {
    const checkRole = async () => {
      const session = await getSession() as Session | null;
      const roles = session?.user?.roles || [];
      setIsManager(roles.some(role => {
        const r = role.toLowerCase();
        return r === 'support_manager' || r === 'admin';
      }));
    };
    checkRole();
  }, []);

  return (
    <Menu>
      <Menu.Item
        to="/"
        primaryText="Дашборд"
        leftIcon={<DashboardIcon/>}
      />
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
      {isManager && (
        <Menu.Item
          to="/nps-report"
          primaryText="Отчет NPS"
          leftIcon={<AssessmentIcon/>}
        />
      )}
    </Menu>
  );
};
export default CustomMenu;
