import app from 'flarum/admin/app';
import OneBotNotifyAdminPage from './components/OneBotNotifyAdminPage';

app.initializers.add('tacraft-sociality-onebot-notify', () => {
  app.registry
    .for('tacraft-sociality-onebot-notify')
    .registerPage(OneBotNotifyAdminPage);
});