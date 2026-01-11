import {SupportTicketList} from "./SupportTicketList";
import {SupportTicketShow} from "./SupportTicketShow";
import {SupportTicketCreate} from "./SupportTicketCreate";
import SettingsIcon from '@mui/icons-material/Settings';

const supportTicketResourceProps = {
  list: SupportTicketList,
  show: SupportTicketShow,
  create: SupportTicketCreate,
  // edit: false,
  hasCreate: true,
  recordRepresentation: (record: any) => record?.subject || 'Заявка',
  options: {
    label: 'Заявки в ТП'
  },
  icon: SettingsIcon
};

export default supportTicketResourceProps;
