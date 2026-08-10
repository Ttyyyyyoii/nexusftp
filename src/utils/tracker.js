export const baseUrl = import.meta.env.VITE_API_URL || '/api'

function generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8)
    return v.toString(16)
  })
}

class Tracker {
  constructor() {
    this.sessionId = this.getOrCreateSession()
    this.currentLogId = null
    this.enterTime = null
    this.deviceType = /Mobi|Android/i.test(navigator.userAgent) ? 'Mobile' : 'Desktop'
    this.os = this.detectOS()
    this.browser = this.detectBrowser()
    this.language = navigator.language || 'Unknown'
    this.screenRes = `${window.screen.width}x${window.screen.height}`
    this.referrer = document.referrer || 'Direct'
    this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Unknown'
    
    window.addEventListener('beforeunload', () => this.trackLeave())
  }

  getOrCreateSession() {
    let sid = sessionStorage.getItem('nuvio_ftp_session_id')
    if (!sid) {
      sid = generateUUID()
      sessionStorage.setItem('nuvio_ftp_session_id', sid)
    }
    return sid
  }

  detectOS() {
    const ua = navigator.userAgent
    if (ua.includes('Win')) return 'Windows'
    if (ua.includes('Mac')) return 'MacOS'
    if (ua.includes('Linux')) return 'Linux'
    if (ua.includes('Android')) return 'Android'
    if (ua.includes('like Mac')) return 'iOS'
    return 'Unknown'
  }

  detectBrowser() {
    const ua = navigator.userAgent
    if (ua.includes('Chrome')) return 'Chrome'
    if (ua.includes('Firefox')) return 'Firefox'
    if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari'
    if (ua.includes('Edge')) return 'Edge'
    if (ua.includes('Opera') || ua.includes('OPR')) return 'Opera'
    return 'Unknown'
  }

  async trackEnter(pageUrl) {
    if (this.currentLogId) {
      await this.trackLeave()
    }

    this.enterTime = Date.now()
    const payload = {
      action: 'enter',
      session_id: this.sessionId,
      page_url: pageUrl,
      device_type: this.deviceType,
      os: this.os,
      browser: this.browser,
      language: this.language,
      screen_res: this.screenRes,
      referrer: this.referrer,
      timezone: this.timezone
    }

    try {
      const res = await fetch(`${baseUrl}/trackVisitor.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        keepalive: true
      })
      const data = await res.json()
      if (data.status === 'success') {
        this.currentLogId = data.log_id
      }
    } catch (e) {
      // Silent error
    }
  }

  async trackLeave() {
    if (!this.currentLogId || !this.enterTime) return

    const timeSpent = Math.floor((Date.now() - this.enterTime) / 1000)
    
    if (timeSpent <= 0) return

    const payload = {
      action: 'leave',
      log_id: this.currentLogId,
      time_spent: timeSpent
    }

    try {
      if (navigator.sendBeacon) {
        navigator.sendBeacon(`${baseUrl}/trackVisitor.php`, JSON.stringify(payload))
      } else {
        await fetch(`${baseUrl}/trackVisitor.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
          keepalive: true
        })
      }
    } catch (e) {}

    this.currentLogId = null
    this.enterTime = null
  }

  init(router) {
    router.afterEach((to) => {
      if (!to.path.startsWith('/admin')) {
        this.trackEnter(to.fullPath)
      }
    })
  }
}

export const tracker = new Tracker()
