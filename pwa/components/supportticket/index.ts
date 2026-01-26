import {SupportTicketList} from "./SupportTicketList";
import {SupportTicketShow} from "./SupportTicketShow";
import SettingsIcon from '@mui/icons-material/Settings';

const supportTicketResourceProps = {
  list: SupportTicketList,
  show: SupportTicketShow,
  // edit: false,
  hasCreate: false,
  recordRepresentation: (record: any) => record?.subject || 'Заявка',
  options: {
    label: 'Техническая поддержка'
  },
  icon: SettingsIcon
};

export default supportTicketResourceProps;
