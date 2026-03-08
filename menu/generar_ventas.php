<html lang="es"><script src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/content/location/location.js" id="eppiocemhmnlbhjplcgkofciiegomcon"></script><script src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/libs/extend-native-history-api.js"></script><script src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/libs/requests.js"></script><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- CSRF meta removed -->
  <title class="text-white">Gestión de Ventas</title>
  <link rel="stylesheet" href="../css/menu.css">
  <link rel="stylesheet" href="../css/tablas.css">
  <link rel="stylesheet" href="../css/factura-modal.css">
  <link rel="stylesheet" href="../DataTables/datatables.min.css">
  <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script bis_use="true" type="text/javascript" charset="utf-8" data-bis-config="[&quot;facebook.com/&quot;,&quot;twitter.com/&quot;,&quot;youtube-nocookie.com/embed/&quot;,&quot;//vk.com/&quot;,&quot;//www.vk.com/&quot;,&quot;linkedin.com/&quot;,&quot;//www.linkedin.com/&quot;,&quot;//instagram.com/&quot;,&quot;//www.instagram.com/&quot;,&quot;//www.google.com/recaptcha/api2/&quot;,&quot;//hangouts.google.com/webchat/&quot;,&quot;//www.google.com/calendar/&quot;,&quot;//www.google.com/maps/embed&quot;,&quot;spotify.com/&quot;,&quot;soundcloud.com/&quot;,&quot;//player.vimeo.com/&quot;,&quot;//disqus.com/&quot;,&quot;//tgwidget.com/&quot;,&quot;//js.driftt.com/&quot;,&quot;friends2follow.com&quot;,&quot;/widget&quot;,&quot;login&quot;,&quot;//video.bigmir.net/&quot;,&quot;blogger.com&quot;,&quot;//smartlock.google.com/&quot;,&quot;//keep.google.com/&quot;,&quot;/web.tolstoycomments.com/&quot;,&quot;moz-extension://&quot;,&quot;chrome-extension://&quot;,&quot;/auth/&quot;,&quot;//analytics.google.com/&quot;,&quot;adclarity.com&quot;,&quot;paddle.com/checkout&quot;,&quot;hcaptcha.com&quot;,&quot;recaptcha.net&quot;,&quot;2captcha.com&quot;,&quot;accounts.google.com&quot;,&quot;www.google.com/shopping/customerreviews&quot;,&quot;buy.tinypass.com&quot;,&quot;gstatic.com&quot;,&quot;secureir.ebaystatic.com&quot;,&quot;docs.google.com&quot;,&quot;contacts.google.com&quot;,&quot;github.com&quot;,&quot;mail.google.com&quot;,&quot;chat.google.com&quot;,&quot;audio.xpleer.com&quot;,&quot;keepa.com&quot;,&quot;static.xx.fbcdn.net&quot;,&quot;sas.selleramp.com&quot;,&quot;1plus1.video&quot;,&quot;console.googletagservices.com&quot;,&quot;//lnkd.demdex.net/&quot;,&quot;//radar.cedexis.com/&quot;,&quot;//li.protechts.net/&quot;,&quot;challenges.cloudflare.com/&quot;,&quot;ogs.google.com&quot;,&quot;//www.ukrnafta.com/data/map/&quot;,&quot;//maps.google.com/maps&quot;,&quot;//www.openstreetmap.org/export/embed.html&quot;,&quot;//www.google.com/maps/d/u/3/embed&quot;]" data-dynamic-id="066d6551-fa88-4e86-8314-94ea22bb8a2f" src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/executors/200.js"></script><script bis_use="true" type="text/javascript" charset="utf-8" nonce="" data-dynamic-id="066d6551-fa88-4e86-8314-94ea22bb8a2f" src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/executors/101.js"></script><style>:root{--swal2-outline: 0 0 0 3px rgba(100, 150, 200, 0.5);--swal2-container-padding: 0.625em;--swal2-backdrop: rgba(0, 0, 0, 0.4);--swal2-backdrop-transition: background-color 0.15s;--swal2-width: 32em;--swal2-padding: 0 0 1.25em;--swal2-border: none;--swal2-border-radius: 0.3125rem;--swal2-background: white;--swal2-color: #545454;--swal2-show-animation: swal2-show 0.3s;--swal2-hide-animation: swal2-hide 0.15s forwards;--swal2-icon-zoom: 1;--swal2-icon-animations: true;--swal2-title-padding: 0.8em 1em 0;--swal2-html-container-padding: 1em 1.6em 0.3em;--swal2-input-border: 1px solid #d9d9d9;--swal2-input-border-radius: 0.1875em;--swal2-input-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.06), 0 0 0 3px transparent;--swal2-input-background: transparent;--swal2-input-transition: border-color 0.2s, box-shadow 0.2s;--swal2-input-hover-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.06), 0 0 0 3px transparent;--swal2-input-focus-border: 1px solid #b4dbed;--swal2-input-focus-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.06), 0 0 0 3px rgba(100, 150, 200, 0.5);--swal2-progress-step-background: #add8e6;--swal2-validation-message-background: #f0f0f0;--swal2-validation-message-color: #666;--swal2-footer-border-color: #eee;--swal2-footer-background: transparent;--swal2-footer-color: inherit;--swal2-timer-progress-bar-background: rgba(0, 0, 0, 0.3);--swal2-close-button-position: initial;--swal2-close-button-inset: auto;--swal2-close-button-font-size: 2.5em;--swal2-close-button-color: #ccc;--swal2-close-button-transition: color 0.2s, box-shadow 0.2s;--swal2-close-button-outline: initial;--swal2-close-button-box-shadow: inset 0 0 0 3px transparent;--swal2-close-button-focus-box-shadow: inset var(--swal2-outline);--swal2-close-button-hover-transform: none;--swal2-actions-justify-content: center;--swal2-actions-width: auto;--swal2-actions-margin: 1.25em auto 0;--swal2-actions-padding: 0;--swal2-actions-border-radius: 0;--swal2-actions-background: transparent;--swal2-action-button-transition: background-color 0.2s, box-shadow 0.2s;--swal2-action-button-hover: black 10%;--swal2-action-button-active: black 10%;--swal2-confirm-button-box-shadow: none;--swal2-confirm-button-border-radius: 0.25em;--swal2-confirm-button-background-color: #7066e0;--swal2-confirm-button-color: #fff;--swal2-deny-button-box-shadow: none;--swal2-deny-button-border-radius: 0.25em;--swal2-deny-button-background-color: #dc3741;--swal2-deny-button-color: #fff;--swal2-cancel-button-box-shadow: none;--swal2-cancel-button-border-radius: 0.25em;--swal2-cancel-button-background-color: #6e7881;--swal2-cancel-button-color: #fff;--swal2-toast-show-animation: swal2-toast-show 0.5s;--swal2-toast-hide-animation: swal2-toast-hide 0.1s forwards;--swal2-toast-border: none;--swal2-toast-box-shadow: 0 0 1px hsl(0deg 0% 0% / 0.075), 0 1px 2px hsl(0deg 0% 0% / 0.075), 1px 2px 4px hsl(0deg 0% 0% / 0.075), 1px 3px 8px hsl(0deg 0% 0% / 0.075), 2px 4px 16px hsl(0deg 0% 0% / 0.075)}[data-swal2-theme=dark]{--swal2-dark-theme-black: #19191a;--swal2-dark-theme-white: #e1e1e1;--swal2-background: var(--swal2-dark-theme-black);--swal2-color: var(--swal2-dark-theme-white);--swal2-footer-border-color: #555;--swal2-input-background: color-mix(in srgb, var(--swal2-dark-theme-black), var(--swal2-dark-theme-white) 10%);--swal2-validation-message-background: color-mix( in srgb, var(--swal2-dark-theme-black), var(--swal2-dark-theme-white) 10% );--swal2-validation-message-color: var(--swal2-dark-theme-white);--swal2-timer-progress-bar-background: rgba(255, 255, 255, 0.7)}@media(prefers-color-scheme: dark){[data-swal2-theme=auto]{--swal2-dark-theme-black: #19191a;--swal2-dark-theme-white: #e1e1e1;--swal2-background: var(--swal2-dark-theme-black);--swal2-color: var(--swal2-dark-theme-white);--swal2-footer-border-color: #555;--swal2-input-background: color-mix(in srgb, var(--swal2-dark-theme-black), var(--swal2-dark-theme-white) 10%);--swal2-validation-message-background: color-mix( in srgb, var(--swal2-dark-theme-black), var(--swal2-dark-theme-white) 10% );--swal2-validation-message-color: var(--swal2-dark-theme-white);--swal2-timer-progress-bar-background: rgba(255, 255, 255, 0.7)}}body.swal2-shown:not(.swal2-no-backdrop,.swal2-toast-shown){overflow:hidden}body.swal2-height-auto{height:auto !important}body.swal2-no-backdrop .swal2-container{background-color:rgba(0,0,0,0) !important;pointer-events:none}body.swal2-no-backdrop .swal2-container .swal2-popup{pointer-events:all}body.swal2-no-backdrop .swal2-container .swal2-modal{box-shadow:0 0 10px var(--swal2-backdrop)}body.swal2-toast-shown .swal2-container{box-sizing:border-box;width:360px;max-width:100%;background-color:rgba(0,0,0,0);pointer-events:none}body.swal2-toast-shown .swal2-container.swal2-top{inset:0 auto auto 50%;transform:translateX(-50%)}body.swal2-toast-shown .swal2-container.swal2-top-end,body.swal2-toast-shown .swal2-container.swal2-top-right{inset:0 0 auto auto}body.swal2-toast-shown .swal2-container.swal2-top-start,body.swal2-toast-shown .swal2-container.swal2-top-left{inset:0 auto auto 0}body.swal2-toast-shown .swal2-container.swal2-center-start,body.swal2-toast-shown .swal2-container.swal2-center-left{inset:50% auto auto 0;transform:translateY(-50%)}body.swal2-toast-shown .swal2-container.swal2-center{inset:50% auto auto 50%;transform:translate(-50%, -50%)}body.swal2-toast-shown .swal2-container.swal2-center-end,body.swal2-toast-shown .swal2-container.swal2-center-right{inset:50% 0 auto auto;transform:translateY(-50%)}body.swal2-toast-shown .swal2-container.swal2-bottom-start,body.swal2-toast-shown .swal2-container.swal2-bottom-left{inset:auto auto 0 0}body.swal2-toast-shown .swal2-container.swal2-bottom{inset:auto auto 0 50%;transform:translateX(-50%)}body.swal2-toast-shown .swal2-container.swal2-bottom-end,body.swal2-toast-shown .swal2-container.swal2-bottom-right{inset:auto 0 0 auto}@media print{body.swal2-shown:not(.swal2-no-backdrop,.swal2-toast-shown){overflow-y:scroll !important}body.swal2-shown:not(.swal2-no-backdrop,.swal2-toast-shown)>[aria-hidden=true]{display:none}body.swal2-shown:not(.swal2-no-backdrop,.swal2-toast-shown) .swal2-container{position:static !important}}div:where(.swal2-container){display:grid;position:fixed;z-index:1060;inset:0;box-sizing:border-box;grid-template-areas:"top-start     top            top-end" "center-start  center         center-end" "bottom-start  bottom-center  bottom-end";grid-template-rows:minmax(min-content, auto) minmax(min-content, auto) minmax(min-content, auto);height:100%;padding:var(--swal2-container-padding);overflow-x:hidden;transition:var(--swal2-backdrop-transition);-webkit-overflow-scrolling:touch}div:where(.swal2-container).swal2-backdrop-show,div:where(.swal2-container).swal2-noanimation{background:var(--swal2-backdrop)}div:where(.swal2-container).swal2-backdrop-hide{background:rgba(0,0,0,0) !important}div:where(.swal2-container).swal2-top-start,div:where(.swal2-container).swal2-center-start,div:where(.swal2-container).swal2-bottom-start{grid-template-columns:minmax(0, 1fr) auto auto}div:where(.swal2-container).swal2-top,div:where(.swal2-container).swal2-center,div:where(.swal2-container).swal2-bottom{grid-template-columns:auto minmax(0, 1fr) auto}div:where(.swal2-container).swal2-top-end,div:where(.swal2-container).swal2-center-end,div:where(.swal2-container).swal2-bottom-end{grid-template-columns:auto auto minmax(0, 1fr)}div:where(.swal2-container).swal2-top-start>.swal2-popup{align-self:start}div:where(.swal2-container).swal2-top>.swal2-popup{grid-column:2;place-self:start center}div:where(.swal2-container).swal2-top-end>.swal2-popup,div:where(.swal2-container).swal2-top-right>.swal2-popup{grid-column:3;place-self:start end}div:where(.swal2-container).swal2-center-start>.swal2-popup,div:where(.swal2-container).swal2-center-left>.swal2-popup{grid-row:2;align-self:center}div:where(.swal2-container).swal2-center>.swal2-popup{grid-column:2;grid-row:2;place-self:center center}div:where(.swal2-container).swal2-center-end>.swal2-popup,div:where(.swal2-container).swal2-center-right>.swal2-popup{grid-column:3;grid-row:2;place-self:center end}div:where(.swal2-container).swal2-bottom-start>.swal2-popup,div:where(.swal2-container).swal2-bottom-left>.swal2-popup{grid-column:1;grid-row:3;align-self:end}div:where(.swal2-container).swal2-bottom>.swal2-popup{grid-column:2;grid-row:3;place-self:end center}div:where(.swal2-container).swal2-bottom-end>.swal2-popup,div:where(.swal2-container).swal2-bottom-right>.swal2-popup{grid-column:3;grid-row:3;place-self:end end}div:where(.swal2-container).swal2-grow-row>.swal2-popup,div:where(.swal2-container).swal2-grow-fullscreen>.swal2-popup{grid-column:1/4;width:100%}div:where(.swal2-container).swal2-grow-column>.swal2-popup,div:where(.swal2-container).swal2-grow-fullscreen>.swal2-popup{grid-row:1/4;align-self:stretch}div:where(.swal2-container).swal2-no-transition{transition:none !important}div:where(.swal2-container)[popover]{width:auto;border:0}div:where(.swal2-container) div:where(.swal2-popup){display:none;position:relative;box-sizing:border-box;grid-template-columns:minmax(0, 100%);width:var(--swal2-width);max-width:100%;padding:var(--swal2-padding);border:var(--swal2-border);border-radius:var(--swal2-border-radius);background:var(--swal2-background);color:var(--swal2-color);font-family:inherit;font-size:1rem;container-name:swal2-popup}div:where(.swal2-container) div:where(.swal2-popup):focus{outline:none}div:where(.swal2-container) div:where(.swal2-popup).swal2-loading{overflow-y:hidden}div:where(.swal2-container) div:where(.swal2-popup).swal2-draggable{cursor:grab}div:where(.swal2-container) div:where(.swal2-popup).swal2-draggable div:where(.swal2-icon){cursor:grab}div:where(.swal2-container) div:where(.swal2-popup).swal2-dragging{cursor:grabbing}div:where(.swal2-container) div:where(.swal2-popup).swal2-dragging div:where(.swal2-icon){cursor:grabbing}div:where(.swal2-container) h2:where(.swal2-title){position:relative;max-width:100%;margin:0;padding:var(--swal2-title-padding);color:inherit;font-size:1.875em;font-weight:600;text-align:center;text-transform:none;overflow-wrap:break-word;cursor:initial}div:where(.swal2-container) div:where(.swal2-actions){display:flex;z-index:1;box-sizing:border-box;flex-wrap:wrap;align-items:center;justify-content:var(--swal2-actions-justify-content);width:var(--swal2-actions-width);margin:var(--swal2-actions-margin);padding:var(--swal2-actions-padding);border-radius:var(--swal2-actions-border-radius);background:var(--swal2-actions-background)}div:where(.swal2-container) div:where(.swal2-loader){display:none;align-items:center;justify-content:center;width:2.2em;height:2.2em;margin:0 1.875em;animation:swal2-rotate-loading 1.5s linear 0s infinite normal;border-width:.25em;border-style:solid;border-radius:100%;border-color:#2778c4 rgba(0,0,0,0) #2778c4 rgba(0,0,0,0)}div:where(.swal2-container) button:where(.swal2-styled){margin:.3125em;padding:.625em 1.1em;transition:var(--swal2-action-button-transition);border:none;box-shadow:0 0 0 3px rgba(0,0,0,0);font-weight:500}div:where(.swal2-container) button:where(.swal2-styled):not([disabled]){cursor:pointer}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-confirm){border-radius:var(--swal2-confirm-button-border-radius);background:initial;background-color:var(--swal2-confirm-button-background-color);box-shadow:var(--swal2-confirm-button-box-shadow);color:var(--swal2-confirm-button-color);font-size:1em}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-confirm):hover{background-color:color-mix(in srgb, var(--swal2-confirm-button-background-color), var(--swal2-action-button-hover))}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-confirm):active{background-color:color-mix(in srgb, var(--swal2-confirm-button-background-color), var(--swal2-action-button-active))}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-deny){border-radius:var(--swal2-deny-button-border-radius);background:initial;background-color:var(--swal2-deny-button-background-color);box-shadow:var(--swal2-deny-button-box-shadow);color:var(--swal2-deny-button-color);font-size:1em}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-deny):hover{background-color:color-mix(in srgb, var(--swal2-deny-button-background-color), var(--swal2-action-button-hover))}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-deny):active{background-color:color-mix(in srgb, var(--swal2-deny-button-background-color), var(--swal2-action-button-active))}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-cancel){border-radius:var(--swal2-cancel-button-border-radius);background:initial;background-color:var(--swal2-cancel-button-background-color);box-shadow:var(--swal2-cancel-button-box-shadow);color:var(--swal2-cancel-button-color);font-size:1em}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-cancel):hover{background-color:color-mix(in srgb, var(--swal2-cancel-button-background-color), var(--swal2-action-button-hover))}div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-cancel):active{background-color:color-mix(in srgb, var(--swal2-cancel-button-background-color), var(--swal2-action-button-active))}div:where(.swal2-container) button:where(.swal2-styled):focus-visible{outline:none;box-shadow:var(--swal2-action-button-focus-box-shadow)}div:where(.swal2-container) button:where(.swal2-styled)[disabled]:not(.swal2-loading){opacity:.4}div:where(.swal2-container) button:where(.swal2-styled)::-moz-focus-inner{border:0}div:where(.swal2-container) div:where(.swal2-footer){margin:1em 0 0;padding:1em 1em 0;border-top:1px solid var(--swal2-footer-border-color);background:var(--swal2-footer-background);color:var(--swal2-footer-color);font-size:1em;text-align:center;cursor:initial}div:where(.swal2-container) .swal2-timer-progress-bar-container{position:absolute;right:0;bottom:0;left:0;grid-column:auto !important;overflow:hidden;border-bottom-right-radius:var(--swal2-border-radius);border-bottom-left-radius:var(--swal2-border-radius)}div:where(.swal2-container) div:where(.swal2-timer-progress-bar){width:100%;height:.25em;background:var(--swal2-timer-progress-bar-background)}div:where(.swal2-container) img:where(.swal2-image){max-width:100%;margin:2em auto 1em;cursor:initial}div:where(.swal2-container) button:where(.swal2-close){position:var(--swal2-close-button-position);inset:var(--swal2-close-button-inset);z-index:2;align-items:center;justify-content:center;width:1.2em;height:1.2em;margin-top:0;margin-right:0;margin-bottom:-1.2em;padding:0;overflow:hidden;transition:var(--swal2-close-button-transition);border:none;border-radius:var(--swal2-border-radius);outline:var(--swal2-close-button-outline);background:rgba(0,0,0,0);color:var(--swal2-close-button-color);font-family:monospace;font-size:var(--swal2-close-button-font-size);cursor:pointer;justify-self:end}div:where(.swal2-container) button:where(.swal2-close):hover{transform:var(--swal2-close-button-hover-transform);background:rgba(0,0,0,0);color:#f27474}div:where(.swal2-container) button:where(.swal2-close):focus-visible{outline:none;box-shadow:var(--swal2-close-button-focus-box-shadow)}div:where(.swal2-container) button:where(.swal2-close)::-moz-focus-inner{border:0}div:where(.swal2-container) div:where(.swal2-html-container){z-index:1;justify-content:center;margin:0;padding:var(--swal2-html-container-padding);overflow:auto;color:inherit;font-size:1.125em;font-weight:normal;line-height:normal;text-align:center;overflow-wrap:break-word;word-break:break-word;cursor:initial}div:where(.swal2-container) input:where(.swal2-input),div:where(.swal2-container) input:where(.swal2-file),div:where(.swal2-container) textarea:where(.swal2-textarea),div:where(.swal2-container) select:where(.swal2-select),div:where(.swal2-container) div:where(.swal2-radio),div:where(.swal2-container) label:where(.swal2-checkbox){margin:1em 2em 3px}div:where(.swal2-container) input:where(.swal2-input),div:where(.swal2-container) input:where(.swal2-file),div:where(.swal2-container) textarea:where(.swal2-textarea){box-sizing:border-box;width:auto;transition:var(--swal2-input-transition);border:var(--swal2-input-border);border-radius:var(--swal2-input-border-radius);background:var(--swal2-input-background);box-shadow:var(--swal2-input-box-shadow);color:inherit;font-size:1.125em}div:where(.swal2-container) input:where(.swal2-input).swal2-inputerror,div:where(.swal2-container) input:where(.swal2-file).swal2-inputerror,div:where(.swal2-container) textarea:where(.swal2-textarea).swal2-inputerror{border-color:#f27474 !important;box-shadow:0 0 2px #f27474 !important}div:where(.swal2-container) input:where(.swal2-input):hover,div:where(.swal2-container) input:where(.swal2-file):hover,div:where(.swal2-container) textarea:where(.swal2-textarea):hover{box-shadow:var(--swal2-input-hover-box-shadow)}div:where(.swal2-container) input:where(.swal2-input):focus,div:where(.swal2-container) input:where(.swal2-file):focus,div:where(.swal2-container) textarea:where(.swal2-textarea):focus{border:var(--swal2-input-focus-border);outline:none;box-shadow:var(--swal2-input-focus-box-shadow)}div:where(.swal2-container) input:where(.swal2-input)::placeholder,div:where(.swal2-container) input:where(.swal2-file)::placeholder,div:where(.swal2-container) textarea:where(.swal2-textarea)::placeholder{color:#ccc}div:where(.swal2-container) .swal2-range{margin:1em 2em 3px;background:var(--swal2-background)}div:where(.swal2-container) .swal2-range input{width:80%}div:where(.swal2-container) .swal2-range output{width:20%;color:inherit;font-weight:600;text-align:center}div:where(.swal2-container) .swal2-range input,div:where(.swal2-container) .swal2-range output{height:2.625em;padding:0;font-size:1.125em;line-height:2.625em}div:where(.swal2-container) .swal2-input{height:2.625em;padding:0 .75em}div:where(.swal2-container) .swal2-file{width:75%;margin-right:auto;margin-left:auto;background:var(--swal2-input-background);font-size:1.125em}div:where(.swal2-container) .swal2-textarea{height:6.75em;padding:.75em}div:where(.swal2-container) .swal2-select{min-width:50%;max-width:100%;padding:.375em .625em;background:var(--swal2-input-background);color:inherit;font-size:1.125em}div:where(.swal2-container) .swal2-radio,div:where(.swal2-container) .swal2-checkbox{align-items:center;justify-content:center;background:var(--swal2-background);color:inherit}div:where(.swal2-container) .swal2-radio label,div:where(.swal2-container) .swal2-checkbox label{margin:0 .6em;font-size:1.125em}div:where(.swal2-container) .swal2-radio input,div:where(.swal2-container) .swal2-checkbox input{flex-shrink:0;margin:0 .4em}div:where(.swal2-container) label:where(.swal2-input-label){display:flex;justify-content:center;margin:1em auto 0}div:where(.swal2-container) div:where(.swal2-validation-message){align-items:center;justify-content:center;margin:1em 0 0;padding:.625em;overflow:hidden;background:var(--swal2-validation-message-background);color:var(--swal2-validation-message-color);font-size:1em;font-weight:300}div:where(.swal2-container) div:where(.swal2-validation-message)::before{content:"!";display:inline-block;width:1.5em;min-width:1.5em;height:1.5em;margin:0 .625em;border-radius:50%;background-color:#f27474;color:#fff;font-weight:600;line-height:1.5em;text-align:center}div:where(.swal2-container) .swal2-progress-steps{flex-wrap:wrap;align-items:center;max-width:100%;margin:1.25em auto;padding:0;background:rgba(0,0,0,0);font-weight:600}div:where(.swal2-container) .swal2-progress-steps li{display:inline-block;position:relative}div:where(.swal2-container) .swal2-progress-steps .swal2-progress-step{z-index:20;flex-shrink:0;width:2em;height:2em;border-radius:2em;background:#2778c4;color:#fff;line-height:2em;text-align:center}div:where(.swal2-container) .swal2-progress-steps .swal2-progress-step.swal2-active-progress-step{background:#2778c4}div:where(.swal2-container) .swal2-progress-steps .swal2-progress-step.swal2-active-progress-step~.swal2-progress-step{background:var(--swal2-progress-step-background);color:#fff}div:where(.swal2-container) .swal2-progress-steps .swal2-progress-step.swal2-active-progress-step~.swal2-progress-step-line{background:var(--swal2-progress-step-background)}div:where(.swal2-container) .swal2-progress-steps .swal2-progress-step-line{z-index:10;flex-shrink:0;width:2.5em;height:.4em;margin:0 -1px;background:#2778c4}div:where(.swal2-icon){position:relative;box-sizing:content-box;justify-content:center;width:5em;height:5em;margin:2.5em auto .6em;zoom:var(--swal2-icon-zoom);border:.25em solid rgba(0,0,0,0);border-radius:50%;border-color:#000;font-family:inherit;line-height:5em;cursor:default;user-select:none}div:where(.swal2-icon) .swal2-icon-content{display:flex;align-items:center;font-size:3.75em}div:where(.swal2-icon).swal2-error{border-color:#f27474;color:#f27474}div:where(.swal2-icon).swal2-error .swal2-x-mark{position:relative;flex-grow:1}div:where(.swal2-icon).swal2-error [class^=swal2-x-mark-line]{display:block;position:absolute;top:2.3125em;width:2.9375em;height:.3125em;border-radius:.125em;background-color:#f27474}div:where(.swal2-icon).swal2-error [class^=swal2-x-mark-line][class$=left]{left:1.0625em;transform:rotate(45deg)}div:where(.swal2-icon).swal2-error [class^=swal2-x-mark-line][class$=right]{right:1em;transform:rotate(-45deg)}@container swal2-popup style(--swal2-icon-animations:true){div:where(.swal2-icon).swal2-error.swal2-icon-show{animation:swal2-animate-error-icon .5s}div:where(.swal2-icon).swal2-error.swal2-icon-show .swal2-x-mark{animation:swal2-animate-error-x-mark .5s}}div:where(.swal2-icon).swal2-warning{border-color:#f8bb86;color:#f8bb86}@container swal2-popup style(--swal2-icon-animations:true){div:where(.swal2-icon).swal2-warning.swal2-icon-show{animation:swal2-animate-error-icon .5s}div:where(.swal2-icon).swal2-warning.swal2-icon-show .swal2-icon-content{animation:swal2-animate-i-mark .5s}}div:where(.swal2-icon).swal2-info{border-color:#3fc3ee;color:#3fc3ee}@container swal2-popup style(--swal2-icon-animations:true){div:where(.swal2-icon).swal2-info.swal2-icon-show{animation:swal2-animate-error-icon .5s}div:where(.swal2-icon).swal2-info.swal2-icon-show .swal2-icon-content{animation:swal2-animate-i-mark .8s}}div:where(.swal2-icon).swal2-question{border-color:#87adbd;color:#87adbd}@container swal2-popup style(--swal2-icon-animations:true){div:where(.swal2-icon).swal2-question.swal2-icon-show{animation:swal2-animate-error-icon .5s}div:where(.swal2-icon).swal2-question.swal2-icon-show .swal2-icon-content{animation:swal2-animate-question-mark .8s}}div:where(.swal2-icon).swal2-success{border-color:#a5dc86;color:#a5dc86}div:where(.swal2-icon).swal2-success [class^=swal2-success-circular-line]{position:absolute;width:3.75em;height:7.5em;border-radius:50%}div:where(.swal2-icon).swal2-success [class^=swal2-success-circular-line][class$=left]{top:-0.4375em;left:-2.0635em;transform:rotate(-45deg);transform-origin:3.75em 3.75em;border-radius:7.5em 0 0 7.5em}div:where(.swal2-icon).swal2-success [class^=swal2-success-circular-line][class$=right]{top:-0.6875em;left:1.875em;transform:rotate(-45deg);transform-origin:0 3.75em;border-radius:0 7.5em 7.5em 0}div:where(.swal2-icon).swal2-success .swal2-success-ring{position:absolute;z-index:2;top:-0.25em;left:-0.25em;box-sizing:content-box;width:100%;height:100%;border:.25em solid rgba(165,220,134,.3);border-radius:50%}div:where(.swal2-icon).swal2-success .swal2-success-fix{position:absolute;z-index:1;top:.5em;left:1.625em;width:.4375em;height:5.625em;transform:rotate(-45deg)}div:where(.swal2-icon).swal2-success [class^=swal2-success-line]{display:block;position:absolute;z-index:2;height:.3125em;border-radius:.125em;background-color:#a5dc86}div:where(.swal2-icon).swal2-success [class^=swal2-success-line][class$=tip]{top:2.875em;left:.8125em;width:1.5625em;transform:rotate(45deg)}div:where(.swal2-icon).swal2-success [class^=swal2-success-line][class$=long]{top:2.375em;right:.5em;width:2.9375em;transform:rotate(-45deg)}@container swal2-popup style(--swal2-icon-animations:true){div:where(.swal2-icon).swal2-success.swal2-icon-show .swal2-success-line-tip{animation:swal2-animate-success-line-tip .75s}div:where(.swal2-icon).swal2-success.swal2-icon-show .swal2-success-line-long{animation:swal2-animate-success-line-long .75s}div:where(.swal2-icon).swal2-success.swal2-icon-show .swal2-success-circular-line-right{animation:swal2-rotate-success-circular-line 4.25s ease-in}}[class^=swal2]{-webkit-tap-highlight-color:rgba(0,0,0,0)}.swal2-show{animation:var(--swal2-show-animation)}.swal2-hide{animation:var(--swal2-hide-animation)}.swal2-noanimation{transition:none}.swal2-scrollbar-measure{position:absolute;top:-9999px;width:50px;height:50px;overflow:scroll}.swal2-rtl .swal2-close{margin-right:initial;margin-left:0}.swal2-rtl .swal2-timer-progress-bar{right:0;left:auto}.swal2-toast{box-sizing:border-box;grid-column:1/4 !important;grid-row:1/4 !important;grid-template-columns:min-content auto min-content;padding:1em;overflow-y:hidden;border:var(--swal2-toast-border);background:var(--swal2-background);box-shadow:var(--swal2-toast-box-shadow);pointer-events:all}.swal2-toast>*{grid-column:2}.swal2-toast h2:where(.swal2-title){margin:.5em 1em;padding:0;font-size:1em;text-align:initial}.swal2-toast .swal2-loading{justify-content:center}.swal2-toast input:where(.swal2-input){height:2em;margin:.5em;font-size:1em}.swal2-toast .swal2-validation-message{font-size:1em}.swal2-toast div:where(.swal2-footer){margin:.5em 0 0;padding:.5em 0 0;font-size:.8em}.swal2-toast button:where(.swal2-close){grid-column:3/3;grid-row:1/99;align-self:center;width:.8em;height:.8em;margin:0;font-size:2em}.swal2-toast div:where(.swal2-html-container){margin:.5em 1em;padding:0;overflow:initial;font-size:1em;text-align:initial}.swal2-toast div:where(.swal2-html-container):empty{padding:0}.swal2-toast .swal2-loader{grid-column:1;grid-row:1/99;align-self:center;width:2em;height:2em;margin:.25em}.swal2-toast .swal2-icon{grid-column:1;grid-row:1/99;align-self:center;width:2em;min-width:2em;height:2em;margin:0 .5em 0 0}.swal2-toast .swal2-icon .swal2-icon-content{display:flex;align-items:center;font-size:1.8em;font-weight:bold}.swal2-toast .swal2-icon.swal2-success .swal2-success-ring{width:2em;height:2em}.swal2-toast .swal2-icon.swal2-error [class^=swal2-x-mark-line]{top:.875em;width:1.375em}.swal2-toast .swal2-icon.swal2-error [class^=swal2-x-mark-line][class$=left]{left:.3125em}.swal2-toast .swal2-icon.swal2-error [class^=swal2-x-mark-line][class$=right]{right:.3125em}.swal2-toast div:where(.swal2-actions){justify-content:flex-start;height:auto;margin:0;margin-top:.5em;padding:0 .5em}.swal2-toast button:where(.swal2-styled){margin:.25em .5em;padding:.4em .6em;font-size:1em}.swal2-toast .swal2-success{border-color:#a5dc86}.swal2-toast .swal2-success [class^=swal2-success-circular-line]{position:absolute;width:1.6em;height:3em;border-radius:50%}.swal2-toast .swal2-success [class^=swal2-success-circular-line][class$=left]{top:-0.8em;left:-0.5em;transform:rotate(-45deg);transform-origin:2em 2em;border-radius:4em 0 0 4em}.swal2-toast .swal2-success [class^=swal2-success-circular-line][class$=right]{top:-0.25em;left:.9375em;transform-origin:0 1.5em;border-radius:0 4em 4em 0}.swal2-toast .swal2-success .swal2-success-ring{width:2em;height:2em}.swal2-toast .swal2-success .swal2-success-fix{top:0;left:.4375em;width:.4375em;height:2.6875em}.swal2-toast .swal2-success [class^=swal2-success-line]{height:.3125em}.swal2-toast .swal2-success [class^=swal2-success-line][class$=tip]{top:1.125em;left:.1875em;width:.75em}.swal2-toast .swal2-success [class^=swal2-success-line][class$=long]{top:.9375em;right:.1875em;width:1.375em}@container swal2-popup style(--swal2-icon-animations:true){.swal2-toast .swal2-success.swal2-icon-show .swal2-success-line-tip{animation:swal2-toast-animate-success-line-tip .75s}.swal2-toast .swal2-success.swal2-icon-show .swal2-success-line-long{animation:swal2-toast-animate-success-line-long .75s}}.swal2-toast.swal2-show{animation:var(--swal2-toast-show-animation)}.swal2-toast.swal2-hide{animation:var(--swal2-toast-hide-animation)}@keyframes swal2-show{0%{transform:translate3d(0, -50px, 0) scale(0.9);opacity:0}100%{transform:translate3d(0, 0, 0) scale(1);opacity:1}}@keyframes swal2-hide{0%{transform:translate3d(0, 0, 0) scale(1);opacity:1}100%{transform:translate3d(0, -50px, 0) scale(0.9);opacity:0}}@keyframes swal2-animate-success-line-tip{0%{top:1.1875em;left:.0625em;width:0}54%{top:1.0625em;left:.125em;width:0}70%{top:2.1875em;left:-0.375em;width:3.125em}84%{top:3em;left:1.3125em;width:1.0625em}100%{top:2.8125em;left:.8125em;width:1.5625em}}@keyframes swal2-animate-success-line-long{0%{top:3.375em;right:2.875em;width:0}65%{top:3.375em;right:2.875em;width:0}84%{top:2.1875em;right:0;width:3.4375em}100%{top:2.375em;right:.5em;width:2.9375em}}@keyframes swal2-rotate-success-circular-line{0%{transform:rotate(-45deg)}5%{transform:rotate(-45deg)}12%{transform:rotate(-405deg)}100%{transform:rotate(-405deg)}}@keyframes swal2-animate-error-x-mark{0%{margin-top:1.625em;transform:scale(0.4);opacity:0}50%{margin-top:1.625em;transform:scale(0.4);opacity:0}80%{margin-top:-0.375em;transform:scale(1.15)}100%{margin-top:0;transform:scale(1);opacity:1}}@keyframes swal2-animate-error-icon{0%{transform:rotateX(100deg);opacity:0}100%{transform:rotateX(0deg);opacity:1}}@keyframes swal2-rotate-loading{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@keyframes swal2-animate-question-mark{0%{transform:rotateY(-360deg)}100%{transform:rotateY(0)}}@keyframes swal2-animate-i-mark{0%{transform:rotateZ(45deg);opacity:0}25%{transform:rotateZ(-25deg);opacity:.4}50%{transform:rotateZ(15deg);opacity:.8}75%{transform:rotateZ(-5deg);opacity:1}100%{transform:rotateX(0);opacity:1}}@keyframes swal2-toast-show{0%{transform:translateY(-0.625em) rotateZ(2deg)}33%{transform:translateY(0) rotateZ(-2deg)}66%{transform:translateY(0.3125em) rotateZ(2deg)}100%{transform:translateY(0) rotateZ(0deg)}}@keyframes swal2-toast-hide{100%{transform:rotateZ(1deg);opacity:0}}@keyframes swal2-toast-animate-success-line-tip{0%{top:.5625em;left:.0625em;width:0}54%{top:.125em;left:.125em;width:0}70%{top:.625em;left:-0.25em;width:1.625em}84%{top:1.0625em;left:.75em;width:.5em}100%{top:1.125em;left:.1875em;width:.75em}}@keyframes swal2-toast-animate-success-line-long{0%{top:1.625em;right:1.375em;width:0}65%{top:1.25em;right:.9375em;width:0}84%{top:.9375em;right:0;width:1.125em}100%{top:.9375em;right:.1875em;width:1.375em}}</style>

  <style>
    /* ===== FONDO EXACTO - MÓDULO VENTAS ===== */
    body {
      background:
        /* Fondo oscuro glassmorphism */
        linear-gradient(rgba(26, 26, 26, 0.95), rgba(15, 15, 15, 0.98)),
        /* Gradiente rojo sutil */
        radial-gradient(circle at 20% 80%, rgba(209, 0, 27, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(197, 2, 2, 0.12) 0%, transparent 50%),
        /* Patrón geométrico sutil */
        linear-gradient(90deg, transparent 48%, rgba(209, 0, 27, 0.03) 50%, rgba(209, 0, 27, 0.03) 52%, transparent 54%);
      background-attachment: fixed;
      background-size: cover, auto, auto, 50px 50px;
      min-height: 100vh;
      position: relative;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      /* background-image:
        linear-gradient(45deg, transparent 49%, rgba(209, 0, 27, 0.04) 50%, rgba(209, 0, 27, 0.04) 51%, transparent 52%),
        radial-gradient(circle at 25% 25%, rgba(197, 2, 2, 0.08) 1px, transparent 1px),
        radial-gradient(circle at 75% 75%, rgba(197, 2, 2, 0.08) 1px, transparent 1px); */
      background-size: 100px 100px, 50px 50px, 50px 50px;
      pointer-events: none;
      z-index: -1;
    }

    /* ===== HEADER VENTAS ===== */
    .header-ventas {
      text-align: center;
      margin-bottom: 2rem;
      padding: 2rem 0;
      background: rgba(35, 35, 35, 0.8);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      border: 1px solid rgba(209, 0, 27, 0.3);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .header-ventas h2 {
      color: #fff;
      font-weight: 700;
      text-shadow: 0 2px 10px rgba(209, 0, 27, 0.5);
      margin-bottom: 0.5rem;
    }

    .header-ventas .subtitle-ventas {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1.1rem;
      font-weight: 400;
    }

    /* ===== TABLA GLASSMORPHISM - VENTAS ===== */
    #tablaVentas_wrapper {
      background: rgba(35, 35, 35, 0.95) !important;
      backdrop-filter: blur(25px) !important;
      border-radius: 15px !important;
      border: 1px solid rgba(209, 0, 27, 0.2) !important;
      overflow: hidden !important;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6) !important;
      width: 90% !important;
      color: white !important;
    }

    .table thead {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .table thead th {
      border: none !important;
      padding: 16px 15px !important;
      font-weight: 600 !important;
    }

    .table td {
      padding: 16px 15px !important;
      border-color: rgba(209, 0, 27, 0.1) !important;
      color: #e0e0e0 !important;
      vertical-align: middle;
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(209, 0, 27, 0.05) !important;
    }

    .table-striped tbody tr:hover {
      background: rgba(209, 0, 27, 0.15) !important;
      transform: scale(1.01);
      transition: all 0.2s ease;
    }

    /* DataTables CONTROLES */
    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label {
      color: #e0e0e0 !important;
      font-weight: 500 !important;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      background: rgba(255, 255, 255, 0.9) !important;
      color: #333 !important;
      border: 1px solid rgba(209, 0, 27, 0.3) !important;
      border-radius: 6px !important;
      padding: 6px 10px !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .dataTables_wrapper .dataTables_info {
      color: #e0e0e0 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      color: #e0e0e0 !important;
      background: rgba(209, 0, 27, 0.2) !important;
      border: 1px solid rgba(209, 0, 27, 0.4) !important;
      border-radius: 6px !important;
      margin: 0 2px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      box-shadow: 0 4px 12px rgba(209, 0, 27, 0.4);
    }

    /* Offcanvas EXACTO */
    .offcanvas {
      background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%) !important;
      border-right: 1px solid rgba(209, 0, 27, 0.3) !important;
      box-shadow: 5px 0 30px rgba(209, 0, 27, 0.2) !important;
    }

    .offcanvas .nav-link {
      color: #e0e0e0 !important;
      border-radius: 8px;
      margin: 4px 8px;
      transition: all 0.3s ease;
    }

    .offcanvas .nav-link:hover,
    .offcanvas .nav-link.active {
      background: linear-gradient(135deg, #d1001b, #a10412) !important;
      color: white !important;
      transform: translateX(5px);
    }

    /* Botones principales VENTAS */
    .btn-primary {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      border: none !important;
      padding: 12px 25px !important;
      font-weight: 600 !important;
      transition: all 0.3s ease !important;
      box-shadow: 0 4px 15px rgba(209, 0, 27, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4) !important;
    }

    .btn-success {
      background: linear-gradient(135deg, #198754 0%, #146c43 100%) !important;
      border: none !important;
    }

    .btn-info {
      background: linear-gradient(135deg, #0dcaf0 0%, #0aa3c1 100%) !important;
      border: none !important;
      font-weight: 600 !important;

    }

    /* MODALES - VENTAS */
    .modal-header {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      font-weight: 600 !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
    }

    /* Navbar */
    .navbar-dark {
      background: linear-gradient(135deg, #1a1a1a 0%, #2d1b1b 100%) !important;
      box-shadow: 0 4px 20px rgba(209, 0, 27, 0.3) !important;
    }

    /* Botones acciones tabla */
    .btn-group-sm .btn {
      border-radius: 6px !important;
      padding: 6px 10px !important;
      font-size: 0.8rem !important;
    }

    /* Botones centrados */
    .botones-ventas {
      display: flex !important;
      justify-content: center !important;
      gap: 1rem;
      width: 100%;
    }

    #btnNuevaVenta,
    #btnVerGraficoEstadistico {
      min-width: 220px;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
      font-style: normal;
    }

    /* Responsive */
    @media (max-width: 768px) {
      #tablaVentas_wrapper {
        margin: 20px 10px !important;
        font-size: 0.9rem !important;
      }

      .btn-primary {
        padding: 10px 20px !important;
        font-size: 0.9rem !important;
      }
    }

    /* ELIMINAR FONDO PERSONALIZADO */
    .background-wrapper {
      background: transparent !important;
    }

    #divPrecioDolar {
      transition: all 0.3s ease !important;
    }

    #precioDolar {
      transition: all 0.3s ease;
    }

    /* ===== MODAL VENTAS - FONDO OSCURO ROJO CORREGIDO ===== */
    #modalRegistrarVenta .modal-content {
      background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%) !important;
      border: 1px solid rgba(209, 0, 27, 0.4) !important;
      box-shadow: 0 25px 70px rgba(209, 0, 27, 0.3) !important;
      backdrop-filter: blur(20px) !important;
      color: #e0e0e0 !important;
    }

    #modalRegistrarVenta .modal-header {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    #modalRegistrarVenta .modal-body {
      background: rgba(35, 35, 35, 0.95) !important;
      color: #e0e0e0 !important;
      padding: 2rem !important;
    }

    #modalRegistrarVenta .modal-footer {
      background: rgba(26, 26, 26, 0.9) !important;
      border-top: 1px solid rgba(209, 0, 27, 0.2) !important;
    }

    /* Formularios en modal - Contraste mejorado */
    #modalRegistrarVenta .form-control,
    #modalRegistrarVenta .form-select {
      background: rgba(255, 255, 255, 0.92) !important;
      color: #333 !important;
      border: 1px solid rgba(209, 0, 27, 0.3) !important;
      border-radius: 8px !important;
    }

    #modalRegistrarVenta .form-control:focus,
    #modalRegistrarVenta .form-select:focus {
      background: #fff !important;
      color: #333 !important;
      border-color: #d1001b !important;
      box-shadow: 0 0 0 0.2rem rgba(209, 0, 27, 0.25) !important;
    }

    /* Tabla productos - Mejor contraste */
    #productosVentaTable {
      background: rgba(15, 15, 15, 0.95) !important;
      border-radius: 12px !important;
      overflow: hidden !important;
    }

    #productosVentaTable thead th {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      border: none !important;
    }

    #productosVentaTable tbody td {
      color: #e0e0e0 !important;
      border-color: rgba(209, 0, 27, 0.15) !important;
    }

    #productosVentaTable tbody tr:hover {
      background: rgba(209, 0, 27, 0.12) !important;
    }

    /* Select2 en modal */
    /* ===== SELECT2 CLIENTES - LEGIBLE BLANCO ===== */
    #modalRegistrarVenta .select2-container--default .select2-selection--single {
      background: rgba(255, 255, 255, 0.95) !important;
      border: 2px solid rgba(209, 0, 27, 0.3) !important;
      height: 48px !important;
      border-radius: 10px !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    #modalRegistrarVenta .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #333 !important;
      line-height: 44px !important;
      padding-left: 16px !important;
    }

    #modalRegistrarVenta .select2-dropdown {
      background: #ffffff !important;
      /* ← BLANCO PURO */
      border: 2px solid rgba(209, 0, 27, 0.4) !important;
      border-radius: 12px !important;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
      margin-top: 4px;
    }

    #modalRegistrarVenta .select2-container--default .select2-results__option {
      color: #333 !important;
      padding: 12px 16px !important;
    }

    #modalRegistrarVenta .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background: linear-gradient(135deg, #d1001b, #a10412) !important;
      color: #fff !important;
    }

    /* Labels mejorados */
    #modalRegistrarVenta .form-label {
      color: #fff !important;
      font-weight: 600 !important;
      margin-bottom: 0.5rem !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    /* Subtotal destacado */
    #modalRegistrarVenta #subtotalProducto,
    #modalRegistrarVenta #totalVentaBs {
      color: #d1001b !important;
      font-weight: 700 !important;
      font-size: 1.2rem !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    /* Botones en modal */
    #modalRegistrarVenta .btn {
      border-radius: 8px !important;
      font-weight: 600 !important;
      padding: 10px 20px !important;
      transition: all 0.3s ease !important;
    }

    #modalRegistrarVenta .btn-primary {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      border: none !important;
    }

    #modalRegistrarVenta .btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
    }

    /* ===== FIX CANVAS MODAL GRÁFICOS ===== */
    .chart-container {
      position: relative;
      height: 350px !important;
      /* ← ALTURA FIJA CRÍTICA */
      width: 100% !important;
      border-radius: 12px;
      background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%);
      border: 2px solid rgba(209, 0, 27, 0.3);
      padding: 15px;
      box-shadow: 0 8px 32px rgba(209, 0, 27, 0.2);
      backdrop-filter: blur(10px);
    }

    .chart-container canvas {
      max-height: 100% !important;
      max-width: 100% !important;
      width: 100% !important;
      height: 100% !important;
    }

    .chart-container {
      position: relative !important;
      height: 320px !important;
      background: linear-gradient(145deg, #1a1a1a, #2d1b21) !important;
      border: 2px solid rgba(209, 0, 27, 0.4) !important;
      border-radius: 15px !important;
      padding: 20px !important;
      margin-bottom: 1rem !important;
    }

    .chart-container canvas {
      filter: drop-shadow(0 4px 12px rgba(209, 0, 27, 0.3)) !important;
    }


    /* Modal responsive */
    @media (max-width: 768px) {
      .chart-container {
        height: 300px !important;
      }

      .modal-xl .modal-body {
        padding: 1.5rem !important;
      }
    }
  </style>


</head>

<body __processed_258c734f-9388-4e39-942f-0d4d11be73ee__="true" bis_register="W3sibWFzdGVyIjp0cnVlLCJleHRlbnNpb25JZCI6ImVwcGlvY2VtaG1ubGJoanBsY2drb2ZjaWllZ29tY29uIiwiYWRibG9ja2VyU3RhdHVzIjp7IkRJU1BMQVkiOiJlbmFibGVkIiwiRkFDRUJPT0siOiJlbmFibGVkIiwiVFdJVFRFUiI6ImVuYWJsZWQiLCJSRURESVQiOiJlbmFibGVkIiwiUElOVEVSRVNUIjoiZW5hYmxlZCIsIklOU1RBR1JBTSI6ImVuYWJsZWQiLCJUSUtUT0siOiJkaXNhYmxlZCIsIkxJTktFRElOIjoiZW5hYmxlZCIsIkNPTkZJRyI6ImRpc2FibGVkIn0sInZlcnNpb24iOiIyLjAuNDMiLCJzY29yZSI6MjAwNDMwfV0=">

  <!-- Navbar superior -->
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid" bis_skin_checked="1">
      <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1">
        <span class="nav-link">Roʞka System</span>
      </a>
      <a class="navbar-brand" href="http://localhost/Roka_Sports/menu/menu.php">Inicio</a>
    </div>
  </nav>

  <!-- Offcanvas Sidebar -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel" bis_skin_checked="1">
    <div class="offcanvas-header" bis_skin_checked="1">
      <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menú</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body p-0" bis_skin_checked="1">
      <a class="nav-link" href="../menu/modulo_proveedor.php"><i class="bi bi-truck me-2"></i>Proveedores</a>
      <a class="nav-link" href="../menu/compras.php"><i class="bi bi-shop me-2"></i>Compras de telas</a>
      <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Inventario</a>

      <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
      <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
      <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
              <a class="nav-link" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
        <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
            <hr class="my-2">

      <a class="nav-link text-danger" href="http://localhost/Roka_Sports/menu/menu.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
    </div>
  </div>


  <!-- Header + Botones centrados -->
  <div class="container-fluid py-4" bis_skin_checked="1">
    <!-- HEADER VENTAS -->
    <div class="header-ventas mb-5 text-center" bis_skin_checked="1">
      <h2 class="mb-2">
        <i class="fas fa-cash-register me-3"></i>VENTAS
      </h2>
      <div class="subtitle-ventas" bis_skin_checked="1">
        Gestión completa de ventas Roʞka Sports
      </div>
    </div>

    <!-- BOTONES CENTRADOS -->
    <div class="d-flex justify-content-center mb-4 botones-ventas" bis_skin_checked="1">
        <button class="btn btn-danger btn-lg px-5 py-3 shadow-lg" onclick="window.location.href='../menu/ventas.php'">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </button>
    </div>

    <!-- ===== SECCIÓN DE PRODUCTOS CON PAGINACIÓN DESDE BBDD ===== -->
<div class="container-fluid mt-5 px-4" bis_skin_checked="1">
    <div class="productos-destacados p-4 rounded-4 shadow-lg" bis_skin_checked="1">
        
        <!-- HEADER CON FILTROS -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4" bis_skin_checked="1">
            <div class="d-flex align-items-center" bis_skin_checked="1">
                <i class="fas fa-box-open fs-2 me-3" style="color: #d1001b;"></i>
                <h3 class="mb-0 text-white fw-bold">Catálogo de Productos</h3>
                <span class="badge bg-danger ms-3 px-3 py-2 rounded-pill shadow-sm" id="totalProductosBadge">Cargando...</span>
            </div>
            
            <!-- FILTROS Y BÚSQUEDA -->
            <div class="d-flex gap-2 mt-3 mt-lg-0" bis_skin_checked="1">
                <select class="form-select form-select-sm bg-dark text-white border-danger" id="filtroCategoria" style="width: 180px;">
                    <option value="">Todas las categorías</option>
                </select>
                <div class="input-group" style="width: 250px;">
                    <input type="text" class="form-control form-control-sm bg-dark text-white border-danger" 
                           id="buscarProducto" placeholder="Buscar producto...">
                    <button class="btn btn-danger btn-sm" type="button" id="btnBuscar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <button class="btn btn-outline-danger btn-sm" id="btnLimpiarFiltros">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- CONTENEDOR DE PRODUCTOS (LOADING) -->
        <div id="productosLoading" class="text-center py-5" bis_skin_checked="1">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Cargando productos...</span>
            </div>
            <p class="text-white-50 mt-3">Cargando catálogo de productos...</p>
        </div>

        <!-- GRID DE PRODUCTOS -->
        <div id="productosGrid" class="row g-4" style="display: none;" bis_skin_checked="1"></div>

        <!-- PRODUCTOS NO ENCONTRADOS -->
        <div id="productosNotFound" class="text-center py-5" style="display: none;" bis_skin_checked="1">
            <i class="fas fa-box-open fa-4x text-white-50 mb-3"></i>
            <h4 class="text-white">No se encontraron productos</h4>
            <p class="text-white-50">Intenta con otros términos de búsqueda</p>
        </div>

        <!-- PAGINACIÓN -->
        <div id="paginacionContainer" class="d-flex flex-wrap align-items-center justify-content-between mt-5" style="display: none !important;" bis_skin_checked="1">
            <div class="text-white-50" id="infoPaginacion"></div>
            <nav aria-label="Navegación de productos">
                <ul class="pagination pagination-danger mb-0" id="paginacionList">
                    <!-- JS genera aquí -->
                </ul>
            </nav>
        </div>

        <!-- BOTÓN VER MÁS (alternativa a paginación) -->
        <div class="text-center mt-4" id="btnVerMasContainer" style="display: none;" bis_skin_checked="1">
            <button class="btn btn-danger px-5 py-3 rounded-pill shadow-lg" id="btnCargarMas">
                <i class="fas fa-sync-alt me-2"></i>Cargar más productos
            </button>
        </div>
    </div>
</div>

<!-- ESTILOS ESPECÍFICOS -->
<style>
    /* Paginación con tema rojo */
    .pagination-danger .page-item .page-link {
        background: rgba(25, 25, 25, 0.9);
        border: 1px solid rgba(209, 0, 27, 0.3);
        color: #e0e0e0;
        margin: 0 3px;
        border-radius: 8px !important;
        transition: all 0.2s ease;
    }
    
    .pagination-danger .page-item.active .page-link {
        background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
        border-color: #d1001b;
        color: white;
        box-shadow: 0 4px 12px rgba(209, 0, 27, 0.4);
    }
    
    .pagination-danger .page-item .page-link:hover {
        background: rgba(209, 0, 27, 0.3);
        border-color: rgba(209, 0, 27, 0.6);
        color: white;
    }
    
    .pagination-danger .page-item.disabled .page-link {
        background: rgba(50, 50, 50, 0.5);
        border-color: rgba(209, 0, 27, 0.1);
        color: rgba(255,255,255,0.3);
    }
    
    /* Tarjetas de producto con altura uniforme */
    .producto-card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .producto-card .card-img {
        height: 220px;
        object-fit: cover;
        transition: transform 0.6s ease;
    }
    
    .producto-card:hover .card-img {
        transform: scale(1.08);
    }
    
    .producto-card .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .producto-card .card-body .mt-auto {
        margin-top: auto;
    }
    
    /* Badge de stock flotante */
    .stock-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 10;
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    
    /* Código overlay */
    .codigo-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 15px;
        background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
        color: rgba(255,255,255,0.8);
        font-size: 0.85rem;
    }
    
    /* Mejoras responsive */
    @media (max-width: 768px) {
        .producto-card .card-img {
            height: 180px;
        }
        
        .pagination-danger .page-link {
            padding: 0.3rem 0.6rem;
            font-size: 0.85rem;
        }
    }
</style>

<!-- PLANTILLA DE TARJETA DE PRODUCTO (OCULTA PARA CLONAR) -->
<template id="producto-card-template">
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card h-100 border-0 bg-dark text-white rounded-4 overflow-hidden producto-card">
            <div class="position-relative">
                <img src="" class="card-img-top" alt="Producto">
                <span class="badge stock-badge px-3 py-2 rounded-pill"></span>
                <div class="codigo-overlay">
                    <i class="fas fa-tag me-1"></i>
                    <span class="producto-codigo"></span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title fw-bold mb-0 producto-nombre"></h5>
                </div>
                <div class="text-white-50 small mb-2">
                    <i class="fas fa-palette me-1"></i>
                    <span class="producto-talla"></span>
                    <span class="mx-1">|</span>
                    <i class="fas fa-tshirt me-1"></i>
                    <span class="producto-tela"></span>
                </div>
                <p class="text-white-50 small mb-2 producto-descripcion"></p>
                <div class="d-flex justify-content-between align-items-center mt-auto pt-3">
                    <div>
                        <span class="text-white-50 small d-block">Precio</span>
                        <span class="h4 fw-bold mb-0" style="color: #d1001b;">$ <span class="producto-precio"></span></span>
                    </div>
                     <button class="btn btn-danger rounded-pill px-4 py-2 btn-agregar-carrito">
                        <i class="fas fa-cart-plus me-2"></i>Agregar
                    </button>
                </div>
                <div class="mt-2 small text-white-50">
                    <i class="fas fa-cubes me-1"></i>
                    <span class="producto-stock"></span>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- ===== MODAL DE CARRITO DE VENTAS ===== -->
<div class="modal fade" id="modalCarritoVenta" tabindex="-1" aria-labelledby="modalCarritoVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header" style="background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);">
                <h5 class="modal-title fw-bold" id="modalCarritoVentaLabel">
                    <i class="fas fa-shopping-cart me-2"></i> Carrito de Venta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- CLIENTE SELECTOR -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <label class="form-label text-white fw-bold">
                            <i class="fas fa-user me-2"></i> Cliente
                        </label>
                        <div class="input-group">
                            <select class="form-select select2-cliente" id="selectCliente" style="width: 100%;">
                                <option value="">Seleccione un cliente...</option>
                            </select>
                            <button class="btn btn-outline-light" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
                                <i class="fas fa-plus"></i> Nuevo
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white fw-bold">
                            <i class="fas fa-credit-card me-2"></i> Tipo de Pago
                        </label>
                        <select class="form-select bg-dark text-white border-danger" id="tipoPago">
                            <option value="Contado" selected>Contado</option>
                            <option value="Crédito">Crédito (7 días)</option>
                        </select>
                    </div>
                </div>
                
                <!-- MONEDA Y TASA (SOLO SI ES DÓLARES) -->
                <div class="row mb-4" id="rowMonedaPago">
                    <div class="col-md-4">
                        <label class="form-label text-white fw-bold">Moneda</label>
                        <select class="form-select bg-dark text-white border-danger" id="monedaPago">
                            <option value="dolares" selected>Dólares ($)</option>
                            <option value="bolivares">Bolívares (Bs)</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="divTasaDolar">
                        <label class="form-label text-white fw-bold">Tasa del Dólar (BCV)</label>
                        <input type="number" class="form-control bg-dark text-white border-danger" id="tasaDolar" disabled step="0.01" value="0.00">
                    </div>
                </div>
                
                <!-- TABLA DE PRODUCTOS EN CARRITO -->
                <div class="table-responsive mt-3">
                    <table class="table table-dark table-striped table-hover" id="tablaCarrito">
                        <thead style="background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Precio Unit.</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="carritoBody">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x text-white-50 mb-3"></i>
                                    <p class="text-white-50">El carrito está vacío</p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">TOTAL (sin IVA):</td>
                                <td id="totalSinIVA">$0.00</td>
                                <td></td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">IVA (16%):</td>
                                <td id="totalIVA">$0.00</td>
                                <td></td>
                            </tr>
                            <tr class="fw-bold fs-5" style="color: #d1001b;">
                                <td colspan="4" class="text-end">TOTAL A PAGAR:</td>
                                <td id="totalVenta">$0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-outline-light" id="btnLimpiarCarrito">
                    <i class="fas fa-trash me-2"></i> Limpiar
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Seguir comprando
                </button>
                <button type="button" class="btn btn-danger" id="btnProcesarVenta">
                    <i class="fas fa-check-circle me-2"></i> Procesar Venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL NUEVO CLIENTE RÁPIDO ===== -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header" style="background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i> Nuevo Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoCliente">
                    <div class="mb-3">
                        <label class="form-label">Cédula</label>
                        <input type="number" class="form-control bg-dark text-white border-danger" id="clienteCedula" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control bg-dark text-white border-danger" id="clienteNombre" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnGuardarCliente">Guardar Cliente</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== INDICADOR DE CARRITO FLOTANTE ===== -->
<div class="carrito-flotante" id="carritoFlotante" style="display: none;">
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        <div class="card bg-dark text-white border-danger" style="width: 300px; backdrop-filter: blur(10px);">
            <div class="card-header bg-danger d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-shopping-cart me-2"></i> Carrito
                </h6>
                <span class="badge bg-light text-dark" id="carritoBadge">0</span>
            </div>
            <div class="card-body p-2">
                <div id="carritoResumen">
                    <p class="text-white-50 small mb-1 text-center">Carrito vacío</p>
                </div>
                <div class="d-flex justify-content-between mt-2 pt-2 border-top border-secondary">
                    <span class="fw-bold">Total:</span>
                    <span class="fw-bold text-danger" id="carritoTotalFlotante">$0.00</span>
                </div>
                <button class="btn btn-danger btn-sm w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalCarritoVenta">
                    <i class="fas fa-credit-card me-2"></i> Ver Carrito
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ESTILOS PARA CARRITO FLOTANTE -->
<style>
    .carrito-flotante {
        transition: all 0.3s ease;
    }
    .carrito-flotante .card {
        box-shadow: 0 10px 30px rgba(209, 0, 27, 0.3);
        border: 1px solid rgba(209, 0, 27, 0.4);
    }
    .cantidad-input {
        width: 70px;
        text-align: center;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(209,0,27,0.3);
        color: white;
        border-radius: 5px;
    }
    .btn-cantidad {
        padding: 2px 8px;
        font-size: 12px;
    }
    .select2-container--default .select2-selection--single {
        background: #1e1e1e !important;
        border: 1px solid rgba(209, 0, 27, 0.5) !important;
        height: 38px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: white !important;
        line-height: 38px !important;
    }
    .select2-dropdown {
        background: #1e1e1e !important;
        border: 1px solid rgba(209, 0, 27, 0.5) !important;
    }
    .select2-results__option {
        color: white !important;
    }
    .select2-results__option--highlighted {
        background: #d1001b !important;
    }
</style>


<!-- ===== MODAL PARA VER FACTURA DESDE HISTORIAL ===== -->
<div class="modal fade" id="modalVerFactura" tabindex="-1" aria-labelledby="modalVerFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header" style="background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);">
                <h5 class="modal-title fw-bold" id="modalVerFacturaLabel">
                    <i class="fas fa-file-invoice me-2"></i> Detalle de Factura
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4" id="contenedorDetalleFactura">
                <div class="text-center py-5">
                    <div class="spinner-border text-danger mb-3" role="status"></div>
                    <p class="text-white-50">Cargando factura...</p>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-outline-light" id="btnImprimirFactura">
                    <i class="fas fa-print me-2"></i> Imprimir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- JS: jQuery, Bootstrap, DataTables -->
    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../js/generar_ventas.js"></script>
    



</body>
</html>