import PeopleAltIcon from '@mui/icons-material/PeopleAlt';
import {UserList} from './UserList';
import {UserShow} from './UserShow';

const userResourceProps = {
  list: UserList,
  show: UserShow,
  hasCreate: false,
  options: {
    label: 'Пользователи'
  },
  icon: PeopleAltIcon,
};

export default userResourceProps;
