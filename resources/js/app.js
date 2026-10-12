//

import { mountChatbot } from './chatbot/mount';
import { mountAdmin } from './admin/mount';

if (document.querySelector('[data-chatbot-admin]')) {
    mountAdmin();
} else {
    mountChatbot();
}
