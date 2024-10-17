/**
 * Gopeed App
 * Defined an App to manage gopeed
 */
var GopeedApp = GopeedApp || {} //Define gopeed App namespace.
/**
 * Constructor UNAS App
 */
GopeedApp.App = function () {
  this.id = 'Gopeed'
  this.name = 'Gopeed'
  this.version = '6.0.1'
  this.active = false
  this.menuIcon = '/apps/gopeed/images/logo.png?v=6.0.1&'
  this.shortcutIcon = '/apps/gopeed/images/logo.png?v=6.0.1&'
  this.entryUrl = '/apps/gopeed/index.html?v=6.0.1&'
  var self = this
  this.GopeedAppWindow = function () {
    if (UNAS.CheckAppState('Gopeed')) {
      return false
    }
    self.window = new MUI.Window({
      id: 'GopeedAppWindow',
      title: UNAS._('Gopeed'),
      icon: '/apps/gopeed/images/logo_small.png?v=6.0.1&',
      loadMethod: 'xhr',
      width: 750,
      height: 480,
      maximizable: false,
      resizable: true,
      scrollbars: false,
      resizeLimit: { x: [200, 2000], y: [150, 1500] },
      contentURL: '/apps/gopeed/index.html?v=6.0.1&',
      require: { css: ['/apps/gopeed/css/index.css'] },
      onBeforeBuild: function () {
        UNAS.SetAppOpenedWindow('Gopeed', 'GopeedAppWindow')
      },
    })
  }
  this.GopeedUninstall = function () {
    UNAS.RemoveDesktopShortcut('Gopeed')
    UNAS.RemoveMenu('Gopeed')
    UNAS.RemoveAppFromGroups('Gopeed', 'ControlPanel')
    UNAS.RemoveAppFromApps('Gopeed')
  }
  new UNAS.Menu(
    'UNAS_App_Internet_Menu',
    this.name,
    this.menuIcon,
    'Gopeed',
    '',
    this.GopeedAppWindow
  )
  new UNAS.RegisterToAppGroup(
    this.name,
    'ControlPanel',
    {
      Type: 'Internet',
      Location: 1,
      Icon: this.shortcutIcon,
      Url: this.entryUrl,
    },
    {}
  )
  var OnChangeLanguage = function (e) {
    UNAS.SetMenuTitle('Gopeed', UNAS._('Gopeed')) //translate menu
    //UNAS.SetShortcutTitle('Gopeed', UNAS._('Gopeed'));
    if (typeof self.window !== 'undefined') {
      UNAS.SetWindowTitle('GopeedAppWindow', UNAS._('Gopeed'))
    }
  }
  UNAS.LoadTranslation(
    '/apps/gopeed/languages/Translation?v=' + this.version,
    OnChangeLanguage
  )
  UNAS.Event.addEvent('ChangeLanguage', OnChangeLanguage)
  UNAS.CreateApp(
    this.name,
    this.shortcutIcon,
    this.GopeedAppWindow,
    this.GopeedUninstall
  )
}

new GopeedApp.App()
