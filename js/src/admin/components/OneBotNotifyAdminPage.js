import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';

const PREFIX = 'tacraft-onebot-notify';

const DEFAULT_ATTR_DISCUSSION = PREFIX + '.default_new_discussion_template';
const DEFAULT_ATTR_POST = PREFIX + '.default_new_post_template';

const PLACEHOLDERS = [
  ['{title}', 'ph_title'],
  ['{author}', 'ph_author'],
  ['{excerpt}', 'ph_excerpt'],
  ['{url}', 'ph_url'],
  ['{tags}', 'ph_tags'],
];

const EXAMPLES = [
  ['ex_minimal', '{url}'],
  ['ex_compact', '新主题：{title}\n作者：{author}\n{url}'],
  ['ex_full', '新主题：{title}\n标签：{tags}\n作者：{author}\n\n{excerpt}\n{url}'],
];

export default class OneBotNotifyAdminPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.setting(PREFIX + '.enabled', '');
    this.setting(PREFIX + '.target_type', 'group');
    this.setting(PREFIX + '.use_proxy', '');
    this.testing = false;

    this.defaultDiscussionTemplate = app.forum.attribute(DEFAULT_ATTR_DISCUSSION);
    this.defaultPostTemplate = app.forum.attribute(DEFAULT_ATTR_POST);
  }

  content(vnode) {
    return m('.ExtensionPage-settings', m('.container', [
      m('.OneBotNotifySettings', [
        this._connectionSection(),
        this._deliverySection(),
        this._tagsSection(),
        this._templatesSection(),
        this._hintsSection(),
        m('.Form-group.Form-controls', this.submitButton()),
      ]),
    ]));
  }

  _section(titleKey, children) {
    return m('.OneBotNotifySettings-section', [
      m('h3', app.translator.trans(PREFIX + '.' + titleKey)),
      m('.OneBotNotifySettings-sectionBody', children),
    ]);
  }

  _connectionSection() {
    return this._section('admin.connection_heading', [
      m('p.helpText', app.translator.trans(PREFIX + '.admin.connection_intro')),
      this._toggle(PREFIX + '.enabled', 'admin.enabled_switch', 'admin.enabled_help'),
      this._textField(PREFIX + '.api_url', 'admin.api_url_label', 'admin.api_url_help', {
        placeholder: 'http://127.0.0.1:5700',
      }),
      this._textField(PREFIX + '.access_token', 'admin.access_token_label', 'admin.access_token_help', {
        type: 'password',
        placeholder: 'your-access-token',
      }),
      m('.Form-group', [
        m('label', app.translator.trans(PREFIX + '.admin.target_type_label')),
        m(
          'select.FormControl',
          {
            value: this.setting(PREFIX + '.target_type', 'group')(),
            onchange: (e) => {
              this.setting(PREFIX + '.target_type')(e.target.value);
              m.redraw();
            },
          },
          [
            m('option', { value: 'group' }, app.translator.trans(PREFIX + '.admin.target_type_group')),
            m('option', { value: 'private' }, app.translator.trans(PREFIX + '.admin.target_type_private')),
          ]
        ),
        m('p.helpText', app.translator.trans(PREFIX + '.admin.target_type_help')),
      ]),
      m('.Form-group', [
        m('label', app.translator.trans(PREFIX + '.admin.target_id_label')),
        m('textarea.FormControl', {
          rows: 3,
          bidi: this.setting(PREFIX + '.target_id'),
          placeholder: '123456789, 987654321',
        }),
        m('p.helpText', app.translator.trans(PREFIX + '.admin.target_id_help')),
      ]),
      this._toggle(PREFIX + '.use_proxy', 'admin.use_proxy_switch', 'admin.use_proxy_help'),
      this._flagOn(PREFIX + '.use_proxy')
        ? this._textField(PREFIX + '.proxy', 'admin.proxy_label', 'admin.proxy_help', {
            placeholder: 'socks5://127.0.0.1:1080',
          })
        : null,
      m('.Form-group.Form-controls', [
        m(
          Button,
          {
            className: 'Button',
            icon: 'fas fa-paper-plane',
            loading: this.testing,
            onclick: this._sendTest.bind(this),
          },
          app.translator.trans(PREFIX + '.admin.test_button')
        ),
      ]),
    ]);
  }

  _deliverySection() {
    return this._section('admin.delivery_heading', [
      m('p.helpText', app.translator.trans(PREFIX + '.admin.delivery_intro')),
      this._textField(PREFIX + '.retry_attempts', 'admin.retry_attempts_label', 'admin.retry_attempts_help', {
        type: 'number', min: 1, max: 5, placeholder: '2',
      }),
      this._textField(PREFIX + '.retry_delay', 'admin.retry_delay_label', 'admin.retry_delay_help', {
        type: 'number', min: 0, max: 30, placeholder: '1',
      }),
      this._textField(PREFIX + '.connect_timeout', 'admin.connect_timeout_label', 'admin.connect_timeout_help', {
        type: 'number', min: 1, max: 30, placeholder: '5',
      }),
      this._textField(PREFIX + '.timeout', 'admin.timeout_label', 'admin.timeout_help', {
        type: 'number', min: 1, max: 60, placeholder: '10',
      }),
    ]);
  }

  _tagsSection() {
    if (!app.data.extensions['flarum-tags']) {
      return this._section('admin.tags_heading', [
        m('p.helpText', app.translator.trans(PREFIX + '.admin.enabled_tags_empty')),
      ]);
    }

    return this._section('admin.tags_heading', [
      this.buildSettingComponent({
        type: 'flarum-tags.select-tags',
        setting: PREFIX + '.enabled_tags',
        help: app.translator.trans(PREFIX + '.admin.enabled_tags_help'),
      }),
    ]);
  }

  _templatesSection() {
    return this._section('admin.templates_heading', [
      m('p.helpText', app.translator.trans(PREFIX + '.admin.templates_intro')),
      m('.Form-group', [
        m('label', app.translator.trans(PREFIX + '.admin.new_discussion_label')),
        m('textarea.FormControl', {
          rows: 7,
          bidi: this.setting(PREFIX + '.new_discussion_template'),
          placeholder: this.defaultDiscussionTemplate,
        }),
      ]),
      m('.Form-group', [
        m('label', app.translator.trans(PREFIX + '.admin.new_post_label')),
        m('textarea.FormControl', {
          rows: 7,
          bidi: this.setting(PREFIX + '.new_post_template'),
          placeholder: this.defaultPostTemplate,
        }),
      ]),
    ]);
  }

  _hintsSection() {
    return this._section('admin.hints_heading', [
      m('details.OneBotNotifySettings-hints', [
        m('summary', app.translator.trans(PREFIX + '.admin.hints_summary')),
        m('h4', app.translator.trans(PREFIX + '.admin.placeholders_heading')),
        m('ul', PLACEHOLDERS.map(([code, key]) =>
          m('li', [m('code', code), ' — ', app.translator.trans(PREFIX + '.admin.' + key)])
        )),
        m('h4', app.translator.trans(PREFIX + '.admin.examples_heading')),
        m('ul.OneBotNotifySettings-examples', EXAMPLES.map(([key, code]) =>
          m('li', [m('pre', code), m('.OneBotNotifySettings-exampleNote', app.translator.trans(PREFIX + '.admin.' + key))])
        )),
      ]),
    ]);
  }

  _textField(key, labelKey, helpKey, attrs = {}) {
    return m('.Form-group', [
      m('label', app.translator.trans(PREFIX + '.' + labelKey)),
      m('input.FormControl', { type: 'text', ...attrs, bidi: this.setting(key) }),
      m('p.helpText', app.translator.trans(PREFIX + '.' + helpKey)),
    ]);
  }

  _toggle(key, labelKey, descKey) {
    return m('.Form-group', [
      m(Switch, {
        state: this._flagOn(key),
        onchange: (value) => {
          this.setting(key)(value ? '1' : '');
          m.redraw();
        },
      }, app.translator.trans(PREFIX + '.' + labelKey)),
      m('p.helpText', app.translator.trans(PREFIX + '.' + descKey)),
    ]);
  }

  _flagOn(key) {
    const value = this.setting(key, '')();
    return value === '1' || value === true || value === 1;
  }

  _sendTest() {
    if (this.testing) return;

    this.testing = true;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/onebot-notify/test',
    }).then(
      (response) => {
        this.testing = false;
        if (response.success) {
          app.alerts.show({ type: 'success' }, app.translator.trans(PREFIX + '.admin.test_ok'));
        } else {
          app.alerts.show({ type: 'error' }, response.error || app.translator.trans(PREFIX + '.admin.test_failed'));
        }
        m.redraw();
      },
      () => {
        this.testing = false;
        app.alerts.show({ type: 'error' }, app.translator.trans(PREFIX + '.admin.test_failed'));
        m.redraw();
      }
    );
  }
}