<style>
  :root{
    --ink:#075B2A; --ink-2:#0B7A3B; --paper:#F4F8F5; --paper-2:#FFFFFF;
    --gold:#D7A928; --gold-dark:#7A5D12; --blue:#0F49A7; --blue-light:#DCE7FB;
    --coral:#C6473B; --coral-light:#F8E0DC; --slate:#5B6472; --slate-light:#EDEBE2; --line:#DAD6C8;
  }
  *{box-sizing:border-box;}
  html,body{margin:0;padding:0;}
  body{font-family:'Inter',sans-serif;background:var(--paper);color:var(--ink);-webkit-font-smoothing:antialiased;}
  h1,h2,h3,.display{font-family:'Space Grotesk',sans-serif;}
  .mono{font-family:'JetBrains Mono',monospace;}
  a{color:inherit;text-decoration:none;}
  button{font-family:inherit;cursor:pointer;}
  .app{display:flex;min-height:100vh;}
  .sidebar{width:264px;flex-shrink:0;background:linear-gradient(180deg,#064C25 0%,#075B2A 58%,#06451F 100%);color:#EFEFE8;padding:24px 18px;display:flex;flex-direction:column;gap:24px;box-shadow:8px 0 30px rgba(3,55,25,.08);}
  .brand .mark{display:flex;align-items:center;gap:10px;}
  .brand .dot{width:10px;height:10px;border-radius:50%;background:var(--gold);box-shadow:0 0 0 3px rgba(222,174,35,0.25);}
  .brand-logo{width:38px;height:38px;object-fit:contain;flex-shrink:0;background:#fff;border-radius:11px;padding:4px;box-shadow:0 5px 16px rgba(0,0,0,.12);}
  .brand h1{font-size:17px;font-weight:600;margin:0;letter-spacing:0.2px;}
  .brand p{font-size:11.5px;color:#9CA7C4;margin:2px 0 0;letter-spacing:0.4px;text-transform:uppercase;}
  .role-switch{display:flex;flex-direction:column;gap:6px;}
  .role-switch .label{font-size:10.5px;text-transform:uppercase;letter-spacing:0.8px;color:#8B96B4;margin-bottom:4px;}
  .role-btn{display:flex;align-items:center;gap:10px;background:transparent;border:1px solid rgba(255,255,255,0.08);color:#C7CEE0;padding:9px 12px;border-radius:8px;font-size:13.5px;text-align:left;transition:all .15s ease;}
  .role-btn:hover{border-color:rgba(255,255,255,0.2);}
  .role-btn.active{background:var(--gold);color:var(--ink);border-color:var(--gold);font-weight:600;}
  .nav{display:flex;flex-direction:column;gap:3px;}
  .nav-item{background:transparent;border:1px solid transparent;color:#D7E2DA;padding:11px 13px;border-radius:11px;font-size:14px;text-align:left;display:flex;align-items:center;gap:10px;width:100%;transition:all .18s ease;}
  .nav-item:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.06);transform:translateX(2px);}
  .nav-item.active{background:linear-gradient(90deg,rgba(215,169,40,.20),rgba(215,169,40,.07));border-color:rgba(215,169,40,.20);color:#FFE8A5;font-weight:700;box-shadow:inset 3px 0 0 var(--gold);}
  .nav-badge{margin-left:auto;background:var(--coral);color:#fff;font-size:10.5px;padding:1px 6px;border-radius:20px;font-weight:600;}
  .sidebar-foot{margin-top:auto;font-size:11px;color:#6E7A9C;line-height:1.6;border-top:1px solid rgba(255,255,255,0.08);padding-top:14px;}
  .main{
    flex:1;padding:32px 42px;max-width:none;position:relative;overflow-x:hidden;min-width:0;
    background:linear-gradient(160deg, #F7F4EC 0%, #EDF3EA 45%, #F7F4EC 100%);
  }
  .main::before,.main::after{content:'';position:fixed;border-radius:50%;filter:blur(90px);pointer-events:none;z-index:0;}
  .main::before{width:340px;height:340px;background:rgba(10,129,16,0.10);top:60px;right:5%;}
  .main::after{width:300px;height:300px;background:rgba(222,174,35,0.14);bottom:10%;right:20%;}
  .main > *{position:relative;z-index:1;}
  .topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;flex-wrap:wrap;gap:14px;}
  .topbar h2{font-size:28px;line-height:1.15;margin:0 0 6px;letter-spacing:-.4px;}
  .topbar .sub{color:var(--slate);font-size:14px;margin:0;line-height:1.5;}
  .flash{padding:10px 14px;border-radius:8px;font-size:13.5px;margin-bottom:18px;}
  .flash-ok{background:var(--blue-light);color:var(--blue);}
  .flash-err{background:var(--coral-light);color:var(--coral);}
  .btn{border:1px solid var(--line);background:var(--paper-2);color:var(--ink);padding:9px 16px;border-radius:8px;font-size:13.5px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:6px;transition:all .12s ease;}
  .btn:hover{border-color:var(--ink);}
  .sidebar .btn:hover{border-color:rgba(255,255,255,0.4);background:rgba(255,255,255,0.06);}
  .btn-primary{background:var(--ink);color:#fff;border-color:var(--ink);}
  .btn-primary:hover{background:var(--ink-2);}
  .btn-gold{background:var(--gold);color:var(--ink);border-color:var(--gold);}
  .btn-sm{padding:6px 12px;font-size:12.5px;}
  .btn-danger{color:var(--coral);border-color:var(--coral-light);background:var(--coral-light);}
  .btn-approve{background:var(--blue-light);border-color:var(--blue-light);color:var(--blue);}
  .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:26px;}
  .stat{
    background:rgba(255,255,255,.82);border:1px solid rgba(255,255,255,.85);border-radius:16px;padding:18px 18px;
    backdrop-filter:blur(14px) saturate(160%);-webkit-backdrop-filter:blur(14px) saturate(160%);
    box-shadow:0 10px 26px rgba(4,72,34,.07);
  }
  .stat .n{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:700;line-height:1;}
  .stat .l{font-size:12px;color:var(--slate);margin-top:2px;}
  .stat-linked{display:flex;flex-direction:column;gap:2px;text-decoration:none;color:inherit;}
  .stat-top{display:flex;align-items:center;gap:12px;}
  .stat-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .stat-icon.i-green{background:var(--ink);color:#fff;}
  .stat-icon.i-gold{background:var(--gold);color:var(--ink);}
  .stat-icon.i-blue{background:var(--blue);color:#fff;}
  .stat-icon.i-purple{background:#5B3E96;color:#fff;}
  .stat-icon svg{width:20px;height:20px;}
  .stat-link{font-size:11.5px;font-weight:600;color:var(--ink);margin-top:8px;}

  .welcome-banner{
    background:linear-gradient(110deg,#034D29 0%,#08743B 58%,#07552F 100%);
    border-radius:20px;padding:34px 38px;margin-bottom:24px;color:#fff;position:relative;overflow:hidden;
    box-shadow:0 16px 34px rgba(5,84,38,.18);isolation:isolate;min-height:190px;
  }
  .welcome-banner::before{
    content:'';position:absolute;inset:0;opacity:0.12;pointer-events:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Ctext x='4' y='20' font-family='monospace' font-size='13' fill='white'%3E01%3C/text%3E%3Ctext x='44' y='44' font-family='monospace' font-size='13' fill='white'%3E10%3C/text%3E%3Ctext x='4' y='68' font-family='monospace' font-size='13' fill='white'%3E11%3C/text%3E%3C/svg%3E");
  }
  .welcome-banner .dashboard-kicker{display:none!important;}
  .welcome-banner h2{font-size:clamp(34px,3.2vw,56px);line-height:1.08;font-weight:800;letter-spacing:-1.3px;margin:0 0 14px;position:relative;z-index:2;color:#fff;}
  .welcome-banner p{margin:0;color:rgba(255,255,255,.96);font-size:clamp(16px,1.35vw,22px);line-height:1.4;font-weight:400;position:relative;z-index:2;max-width:850px;}

  .agenda-row{display:flex;gap:15px;padding:15px 0;border-bottom:1px solid #E7ECE8;transition:background .15s ease;}
  .agenda-row:last-child{border-bottom:none;} .agenda-row:hover{background:#F8FBF9;border-radius:10px;padding-left:8px;padding-right:8px;}
  .agenda-date{width:62px;flex-shrink:0;text-align:center;background:linear-gradient(160deg,var(--ink),var(--ink-2));color:#fff;border-radius:12px;padding:9px 4px;box-shadow:0 7px 14px rgba(5,91,42,.16);}
  .agenda-date .am{font-size:9.5px;text-transform:uppercase;letter-spacing:0.5px;color:var(--gold);}
  .agenda-date .ad{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;line-height:1.1;}
  .agenda-body{flex:1;min-width:0;}
  .agenda-top{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:4px;}
  .agenda-title{font-size:14.5px;font-weight:600;margin:0;}
  .agenda-meta{font-size:12px;color:var(--slate);display:flex;flex-wrap:wrap;gap:10px;margin-bottom:6px;}
  .avatar-stack{display:flex;align-items:center;}
  .avatar-stack .avatar{border:2px solid var(--paper-2);margin-left:-8px;}
  .avatar-stack .avatar:first-child{margin-left:0;}
  .avatar-stack .more-chip{width:26px;height:26px;border-radius:50%;background:var(--slate-light);color:var(--slate);font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid var(--paper-2);margin-left:-8px;}

  .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;}
  .cal-dow{text-align:center;font-size:10.5px;font-weight:700;color:var(--slate);text-transform:uppercase;padding:6px 0;}
  .cal-day{aspect-ratio:1;border-radius:8px;padding:5px;font-size:12px;color:var(--ink);background:rgba(255,255,255,0.4);position:relative;text-decoration:none;display:block;transition:background .12s ease;}
  .cal-day:hover{background:rgba(255,255,255,0.75);}
  .cal-day.empty{background:transparent;pointer-events:none;}
  .cal-day.has-event{background:var(--blue-light);font-weight:700;}
  .cal-day.today{outline:2px solid var(--gold);}
  .cal-day.selected{background:var(--ink);color:#fff;font-weight:700;}
  .cal-day.selected .dot{background:var(--gold);}
  .cal-day .dot{position:absolute;bottom:4px;left:50%;transform:translateX(-50%);width:5px;height:5px;border-radius:50%;background:var(--blue);}
  .tickets{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;}
  .ticket{
    background:rgba(255,255,255,0.58);
    border:1px solid rgba(255,255,255,0.65);
    border-radius:14px;overflow:hidden;position:relative;padding:16px 18px;
    display:flex;flex-direction:column;
    backdrop-filter:blur(16px) saturate(160%);
    -webkit-backdrop-filter:blur(16px) saturate(160%);
    box-shadow:0 8px 20px rgba(11,80,19,0.06), inset 0 1px 0 rgba(255,255,255,0.5);
    transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease;
  }
  .ticket:hover{transform:translateY(-3px);box-shadow:0 14px 30px rgba(11,80,19,0.14);border-color:rgba(222,174,35,0.5);}
  .ticket-top-row{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;}
  .ticket-date-badge{font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:600;letter-spacing:0.3px;color:var(--ink);background:rgba(11,80,19,0.08);padding:4px 9px;border-radius:20px;}
  .ticket-title{font-size:16px;font-weight:600;margin:0 0 6px;}
  .ticket-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:12.5px;color:var(--slate);margin-bottom:2px;}
  .meta-icon{width:14px;height:14px;flex-shrink:0;vertical-align:-2.5px;margin-right:4px;color:var(--slate);}
  .cat-icon{width:13px;height:13px;flex-shrink:0;vertical-align:-2px;margin-right:5px;}
  .cat-chip{display:inline-flex;align-items:center;font-size:11.5px;font-weight:600;padding:3px 9px 3px 7px;border-radius:20px;background:var(--slate-light);color:var(--ink);}
  .cat-chip.academic{background:#E4D9F5;color:#5B3E96;}
  .cat-chip.sports{background:var(--coral-light);color:#93392F;}
  .cat-chip.cultural{background:#F3E3D3;color:#8A5A18;}
  .cat-chip.outreach{background:var(--blue-light);color:var(--blue);}
  .cat-chip.organizational{background:#FBF1DF;color:var(--gold-dark);}
  .countdown-chip{display:inline-flex;align-self:flex-start;align-items:center;gap:4px;font-size:10.5px;font-weight:700;letter-spacing:0.3px;text-transform:uppercase;padding:2px 8px;border-radius:20px;background:var(--gold);color:var(--ink);}
  .search-bar{position:relative;}
  .search-bar .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--slate);pointer-events:none;}
  .search-bar input[name="q"]{padding-left:36px;}

  .filter-tabs{display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;}
  .filter-tab{padding:7px 15px;border-radius:8px;font-size:13px;font-weight:600;color:var(--slate);background:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.6);}
  .filter-tab:hover{border-color:var(--gold);}
  .filter-tab.active{background:var(--ink);color:#fff;border-color:var(--ink);}
  .filters-panel{background:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.65);border-radius:12px;padding:14px 16px;margin-bottom:18px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);display:none;}
  .filters-panel.show{display:block;}
  .filters-panel .cat-check{display:inline-flex;align-items:center;gap:6px;margin:4px 10px 4px 0;font-size:13px;}

  .browse-rows{display:flex;flex-direction:column;gap:12px;}
  .browse-row{
    display:flex;gap:16px;background:rgba(255,255,255,0.58);border:1px solid rgba(255,255,255,0.65);border-radius:14px;
    padding:14px 16px;backdrop-filter:blur(16px) saturate(160%);-webkit-backdrop-filter:blur(16px) saturate(160%);
    box-shadow:0 8px 20px rgba(11,80,19,0.06), inset 0 1px 0 rgba(255,255,255,0.5);
    transition:transform .15s ease,box-shadow .15s ease;align-items:stretch;
  }
  .browse-row:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(11,80,19,0.12);}
  .browse-date{width:64px;flex-shrink:0;border-radius:10px;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px 4px;text-align:center;}
  .browse-date .bm{font-size:10px;text-transform:uppercase;letter-spacing:0.5px;opacity:0.9;}
  .browse-date .bd{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;line-height:1.05;}
  .browse-date .bw{font-size:9.5px;opacity:0.85;}
  .browse-body{flex:1;min-width:0;}
  .browse-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;}
  .browse-title{font-size:15px;font-weight:600;margin:0 0 6px;}
  .browse-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:12.5px;color:var(--slate);margin-bottom:6px;}
  .browse-desc{font-size:12.5px;color:#454C5A;margin:0 0 8px;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;}
  .browse-side{flex-shrink:0;display:flex;flex-direction:column;align-items:flex-end;justify-content:space-between;gap:8px;}

  .settings-header{display:flex;align-items:center;gap:16px;margin-bottom:26px;}
  .settings-header .name{font-size:17px;font-weight:700;}
  .settings-header .sub{font-size:12.5px;color:var(--slate);margin-top:1px;}
  .settings-header .edit-link{font-size:12px;font-weight:600;color:var(--gold-dark);margin-top:4px;display:inline-block;}
  .settings-group-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--slate);margin:22px 0 8px;}
  .settings-group-title:first-of-type{margin-top:0;}
  .settings-menu{
    background:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.65);border-radius:12px;overflow:hidden;
    backdrop-filter:blur(14px) saturate(160%);-webkit-backdrop-filter:blur(14px) saturate(160%);
  }
  .settings-row{display:flex;align-items:center;gap:12px;padding:13px 16px;text-decoration:none;color:var(--ink);border-bottom:1px solid rgba(218,214,200,0.5);}
  .settings-row:last-child{border-bottom:none;}
  .settings-row:hover{background:rgba(255,255,255,0.5);}
  .settings-row .settings-icon{width:32px;height:32px;border-radius:9px;background:rgba(11,80,19,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--ink);}
  .settings-row .settings-icon svg{width:16px;height:16px;}
  .settings-row .settings-label{flex:1;font-size:13.5px;font-weight:600;}
  .settings-row .settings-chevron{width:16px;height:16px;color:var(--slate);flex-shrink:0;}
  .settings-row.danger{color:var(--coral);}
  .settings-row.danger .settings-icon{background:var(--coral-light);color:var(--coral);}
  .settings-row.danger .settings-label{color:var(--coral);}

  /* ---- Browse Events: centered search + category pills + hero cards ---- */

  .browse-events-header{position:relative;display:flex;align-items:flex-start;justify-content:center;margin-bottom:34px;min-height:0;}
  .browse-events-search-top{width:100%;padding-top:0;}
  .browse-events-header .topbar-actions{position:absolute;right:0;top:0;display:flex;gap:8px;}
  .event-search-form{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;max-width:620px;margin:0 auto;}
  .event-search-box{position:relative;flex:1;min-width:0;}
  .event-search-box input{width:100%;height:44px;padding:0 42px 0 40px;border:1px solid var(--line);border-radius:12px;background:#fff;font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;}
  .event-search-box input:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(11,80,19,.10);}
  .event-search-box .search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:var(--slate);pointer-events:none;}
  .event-search-clear{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:22px;line-height:1;color:var(--slate);text-decoration:none;}
  .event-search-clear:hover{color:var(--green);}
  .event-search-button{height:44px;padding:0 20px;border-radius:12px;white-space:nowrap;}
  @media (max-width:760px){
    .browse-events-header{padding-top:0;padding-bottom:0;margin-bottom:28px;}
    .browse-events-header .topbar-actions{position:static;margin-top:14px;justify-content:center;}
    .event-search-form{flex-direction:column;align-items:stretch;}
    .event-search-button{width:100%;}
  }

  .search-center-wrap{display:flex;flex-direction:column;align-items:center;gap:14px;margin-bottom:0;}
  .search-center-wrap .search-bar{width:100%;max-width:520px;margin:0 auto;}
  .search-center-wrap .search-bar input[name="q"]{
    width:100%;background:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.65);
    backdrop-filter:blur(14px) saturate(160%);-webkit-backdrop-filter:blur(14px) saturate(160%);
  }
  .search-center-wrap .search-bar input[name="q"]:focus{background:rgba(255,255,255,0.8);}
  .pill-tabs{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;}
  .pill-tab{
    padding:8px 18px;border-radius:20px;font-size:13px;font-weight:600;color:var(--ink);
    background:rgba(255,255,255,0.45);border:1px solid rgba(255,255,255,0.6);
    backdrop-filter:blur(12px) saturate(160%);-webkit-backdrop-filter:blur(12px) saturate(160%);
  }
  .pill-tab:hover{border-color:var(--gold);background:rgba(255,255,255,0.65);}
  .pill-tab.active{background:var(--ink);color:#fff;border-color:var(--ink);}

  .hero-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;}
  .hero-card{
    background:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.6);border-radius:16px;overflow:hidden;
    backdrop-filter:blur(22px) saturate(180%);-webkit-backdrop-filter:blur(22px) saturate(180%);
    box-shadow:0 10px 26px rgba(11,80,19,0.1), inset 0 1px 0 rgba(255,255,255,0.6);text-decoration:none;color:inherit;display:block;
    transition:transform .15s ease,box-shadow .15s ease,background .15s ease;
  }
  .hero-card:hover{transform:translateY(-4px);box-shadow:0 16px 34px rgba(11,80,19,0.18), inset 0 1px 0 rgba(255,255,255,0.6);background:rgba(255,255,255,0.62);}
  .hero-card-img{width:100%;aspect-ratio:16/10;object-fit:cover;display:block;background:var(--slate-light);}
  .hero-card-placeholder{width:100%;aspect-ratio:16/10;display:flex;align-items:center;justify-content:center;position:relative;}
  .hero-card-placeholder svg{width:44px;height:44px;color:rgba(255,255,255,0.55);}
  .hero-card-media{position:relative;}
  .hero-status-badge{position:absolute;top:10px;left:10px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:rgba(255,255,255,0.92);color:var(--ink);}
  .hero-status-badge.upcoming{background:var(--blue);color:#fff;}
  .hero-card-body{padding:14px 16px 16px;}
  .hero-card-cat{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--slate);margin-bottom:4px;}
  .hero-card-title{font-size:15.5px;font-weight:700;margin:0 0 6px;color:var(--ink);}
  .hero-card-desc{font-size:12.5px;color:#454C5A;margin:0 0 10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;}
  .hero-card-meta{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--slate);margin-bottom:8px;}
  .hero-card-rating{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--slate);padding-top:8px;border-top:1px solid rgba(218,214,200,0.5);}

  /* ---- Event detail: hero header ---- */
  .detail-hero-img{width:100%;max-height:360px;object-fit:cover;display:block;border-radius:16px;margin-bottom:18px;}
  .detail-hero-placeholder{width:100%;height:220px;border-radius:16px;margin-bottom:18px;display:flex;align-items:center;justify-content:center;}
  .detail-hero-placeholder svg{width:60px;height:60px;color:rgba(255,255,255,0.5);}
  .detail-rating-row{display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;}

  .organizer-card{
    width:190px;height:190px;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;
    background:radial-gradient(circle at 35% 25%,rgba(255,255,255,.98),rgba(246,251,247,.9));
    border:2px solid rgba(31,139,76,.16);border-radius:50%;padding:20px;margin:0 0 14px;
    backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);box-shadow:0 10px 24px rgba(11,80,19,.10);
    overflow:hidden;text-align:center;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;
  }
  .organizer-card:hover{transform:translateY(-4px);box-shadow:0 16px 30px rgba(11,80,19,.15);border-color:rgba(31,139,76,.3);}
  .organizer-card .avatar{width:64px!important;height:64px!important;margin-bottom:2px;border:3px solid #fff;box-shadow:0 5px 14px rgba(11,80,19,.14);}
  .organizer-card .oname{font-size:13.5px;font-weight:700;max-width:145px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .organizer-card .oposition{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.45px;color:var(--gold-dark);margin:0;max-width:145px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .organizer-card .obio{font-size:11px;color:#454C5A;line-height:1.35;margin:0;max-width:145px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
  .organizer-card .oemail{font-size:9.5px;color:var(--slate);display:flex;align-items:center;justify-content:center;gap:4px;max-width:145px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .organizer-card .oemail svg{width:11px;height:11px;flex-shrink:0;}
  .organizer-list{display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;}
  @media(max-width:650px){.organizer-card{width:165px;height:165px;padding:16px}.organizer-card .avatar{width:56px!important;height:56px!important}.organizer-card .oname{font-size:12.5px}.organizer-card .oposition{font-size:8px}.organizer-card .oemail{font-size:8.5px}}

  /* ---- Floating event rating bubble ---- */
  .floating-rate-btn{
    position:fixed !important;right:20px !important;left:auto !important;top:50% !important;bottom:auto !important;transform:translateY(-50%) !important;z-index:9999;
    min-width:58px;min-height:58px;padding:10px 13px;
    background:linear-gradient(145deg,#087A3B,#075B2A);color:#fff;border:2px solid rgba(255,255,255,.92);
    border-radius:999px;display:inline-flex;align-items:center;justify-content:center;gap:7px;
    box-shadow:0 12px 30px rgba(7,91,42,.28),0 0 0 5px rgba(31,139,76,.08);
    font-size:12px;font-weight:800;line-height:1.2;text-decoration:none;
    cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,background .2s ease;
    animation:rateBubbleFloat 3.6s ease-in-out infinite;
  }
  .floating-rate-btn:before{
    content:"";position:absolute;inset:-7px;border-radius:inherit;border:1px solid rgba(31,139,76,.22);
    animation:rateBubblePulse 2.6s ease-out infinite;pointer-events:none;
  }
  .floating-rate-btn:after{
    content:"";position:absolute;left:auto;right:-5px;bottom:5px;width:13px;height:13px;
    background:#087A3B;border-right:2px solid #fff;border-bottom:2px solid #fff;
    transform:rotate(35deg);border-radius:2px;z-index:-1;
  }
  .floating-rate-btn:hover{
    background:linear-gradient(145deg,#0A9148,#087A3B);
    transform:translateY(-50%) translateX(-4px) scale(1.04);
    box-shadow:0 16px 34px rgba(7,91,42,.34),0 0 0 6px rgba(31,139,76,.10);
  }
  .floating-rate-btn:active{transform:translate3d(0,-50%,0) scale(.97) !important;}
  .floating-rate-btn .meta-icon{width:19px;height:19px;flex:0 0 19px;color:#FFD66B;}
  .floating-rate-btn .rate-count{
    background:#FFD66B;color:#075B2A;border-radius:50%;min-width:21px;height:21px;padding:0 5px;
    font-size:10px;font-weight:850;display:inline-flex;align-items:center;justify-content:center;
  }
  @keyframes rateBubbleFloat{0%,100%{margin-top:0}50%{margin-top:-6px}}
  @keyframes rateBubblePulse{0%{opacity:.65;transform:scale(.92)}70%,100%{opacity:0;transform:scale(1.25)}}
  @media(max-width:700px){
    .floating-rate-btn{right:10px !important;left:auto !important;min-width:50px;min-height:50px;padding:9px;border-width:1.5px;}
    .floating-rate-btn .rate-count{min-width:19px;height:19px;}
  }
  @media(prefers-reduced-motion:reduce){
    .floating-rate-btn,.floating-rate-btn:before{animation:none;}
  }
  .ticket-desc{font-size:13px;color:#454C5A;line-height:1.5;margin:6px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
  .ticket-actions{display:flex;gap:8px;margin-top:auto;padding-top:10px;align-items:center;flex-wrap:wrap;}
  .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;}
  .badge-pending{background:#FBEFDD;color:var(--gold-dark);}
  .badge-approved{background:var(--blue-light);color:var(--blue);}
  .badge-rejected{background:var(--coral-light);color:var(--coral);}
  .badge-full{background:var(--slate-light);color:var(--slate);}
  .cap-bar{height:5px;background:var(--slate-light);border-radius:4px;width:140px;overflow:hidden;}
  .cap-fill{height:100%;background:var(--blue);}
  .empty{text-align:center;padding:50px 20px;color:var(--slate);border:1px dashed var(--line);border-radius:12px;background:var(--paper-2);}
  .empty .big{font-family:'Space Grotesk',sans-serif;font-size:16px;color:var(--ink);margin-bottom:6px;font-weight:600;}
  .overlay{
    position:fixed !important;inset:0 !important;
    width:100vw;height:100vh;
    background:rgba(20,33,61,0.45);display:none;align-items:flex-start;justify-content:center;
    padding:60px 20px;z-index:2147483646 !important;overflow:auto;
  }
  .overlay.show{display:flex;}
  .modal{background:var(--paper-2);border-radius:14px;width:100%;max-width:520px;padding:26px 28px 28px;box-shadow:0 20px 60px rgba(20,33,61,0.25);}
  .modal h3{margin:0 0 4px;font-size:19px;}
  .modal .msub{color:var(--slate);font-size:13px;margin:0 0 18px;}
  .field{margin-bottom:14px;}
  .field label{display:block;font-size:12.5px;font-weight:600;color:var(--ink);margin-bottom:5px;}
  .field .hint{font-size:11.5px;color:var(--slate);margin-top:4px;}
  input,textarea,select{width:100%;border:1px solid var(--line);border-radius:8px;padding:9px 11px;font-family:'Inter',sans-serif;font-size:13.5px;background:var(--paper);color:var(--ink);}
  input:focus,textarea:focus,select:focus{outline:2px solid var(--gold);outline-offset:0;border-color:var(--gold);}
  textarea{resize:vertical;min-height:64px;}
  .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;}
  .section-title{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:var(--slate);margin:26px 0 12px;}
  table{width:100%;border-collapse:collapse;font-size:13px;}
  th{text-align:left;color:var(--slate);font-weight:600;padding:8px 10px;border-bottom:1px solid var(--line);font-size:11.5px;text-transform:uppercase;}
  td{padding:9px 10px;border-bottom:1px solid var(--line);}
  tr:last-child td{border-bottom:none;}
  .back-link{font-size:13px;color:var(--slate);margin-bottom:14px;display:inline-block;}
  .back-link:hover{color:var(--ink);}
  .ticket-title a:hover{text-decoration:underline;}
  .detail-card{background:rgba(255,255,255,.9);border:1px solid #E1EAE4;border-radius:16px;padding:22px 24px;margin-bottom:22px;box-shadow:0 10px 26px rgba(4,72,34,.055);}
  .detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin:16px 0;}
  .detail-item .k{font-size:11px;text-transform:uppercase;letter-spacing:0.4px;color:var(--slate);margin-bottom:3px;display:flex;align-items:center;gap:5px;}
  .detail-item .k .meta-icon{margin-right:0;width:12px;height:12px;}
  .detail-item .v{font-size:14px;font-weight:600;}
  .detail-desc{font-size:14px;line-height:1.6;color:#3B4250;margin-top:10px;}
  .feedback-thread{display:flex;flex-direction:column;gap:12px;margin:18px 0;}
  .bubble{max-width:640px;border-radius:14px;padding:10px 14px;background:var(--paper-2);border:1px solid var(--line);position:relative;}
  .bubble.mine{background:#FBF1DF;border-color:#EFDBAE;}
  .bubble-head{display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;}
  .bubble-name{font-size:12.5px;font-weight:700;}
  .bubble-role{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;padding:1px 7px;border-radius:20px;background:var(--slate-light);color:var(--slate);}
  .bubble-role.officer{background:var(--blue-light);color:var(--blue);}
  .bubble-role.admin{background:#E4D9F5;color:#5B3E96;}
  .bubble-time{font-size:11px;color:var(--slate);margin-left:auto;}
  .bubble-msg{font-size:13.5px;line-height:1.5;color:#2C3140;word-wrap:break-word;}
  .bubble-del{font-size:11px;color:var(--coral);background:none;border:none;padding:0;margin-top:6px;text-decoration:underline;}
  .feedback-form{display:flex;gap:10px;align-items:flex-start;margin-top:10px;}
  .feedback-form textarea{min-height:44px;flex:1;}
  .feedback-empty{color:var(--slate);font-size:13.5px;padding:16px 0;text-align:center;}
  .search-bar{display:flex;gap:8px;margin-bottom:22px;}
  .search-bar input{flex:1;max-width:360px;}
  .avatar{border-radius:50%;object-fit:cover;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;}
  .avatar-fallback{background:var(--ink);color:#F3D9A8;font-weight:700;letter-spacing:0.5px;}
  .organizer-row{display:flex;align-items:center;gap:10px;margin-top:2px;}
  .organizer-row .name{font-size:13.5px;font-weight:600;}
  .organizer-row .role{font-size:11.5px;color:var(--slate);}
  .star-input{display:flex;gap:4px;font-size:22px;flex-direction:row-reverse;justify-content:flex-end;}
  .star-input input{display:none;}
  .star-input label{color:var(--slate-light);cursor:pointer;transition:color .1s;}
  .star-input input:checked ~ label,
  .star-input label:hover,
  .star-input label:hover ~ label{color:var(--gold);}
  .rating-summary{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate);margin-top:4px;}
  .event-map{height:220px;border-radius:10px;overflow:hidden;margin:14px 0;border:1px solid var(--line);}
  .picker-map{height:220px;border-radius:10px;overflow:hidden;margin-top:8px;border:1px solid var(--line);}
  .organizer-grid{display:flex;flex-wrap:wrap;gap:20px;margin:14px 0 20px;}
  .organizer-item{display:flex;flex-direction:column;align-items:center;text-align:center;width:84px;}
  .organizer-item .avatar{margin-bottom:8px;}
  .organizer-item .oname{font-size:12px;font-weight:600;color:var(--ink);line-height:1.3;}
  .photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin:14px 0;}
  .photo-grid .ph{position:relative;border-radius:10px;overflow:hidden;aspect-ratio:4/3;background:var(--slate-light);}
  .photo-grid .ph img{width:100%;height:100%;object-fit:cover;display:block;}
  .photo-grid .ph .del-photo{position:absolute;top:5px;right:5px;background:rgba(20,33,61,0.65);color:#fff;border:none;border-radius:6px;font-size:10.5px;padding:3px 7px;cursor:pointer;}
  .detail-layout{display:grid;grid-template-columns:1.6fr 1fr;gap:22px;align-items:start;}
  .detail-side{background:var(--paper-2);border:1px solid var(--line);border-radius:12px;padding:18px 18px 16px;position:sticky;top:20px;}
  .detail-side .section-title{margin-top:0;}
  @media (max-width:960px){
    .detail-layout{grid-template-columns:1fr;}
    .detail-side{position:static;}
  }
  .poll-option{border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:10px;background:var(--paper-2);}
  .poll-option-row{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:6px;}
  .poll-option-label{font-size:13.5px;font-weight:600;}
  .poll-option-pct{font-size:12.5px;color:var(--slate);font-weight:600;}
  .poll-bar{height:8px;background:var(--slate-light);border-radius:5px;overflow:hidden;}
  .poll-bar-fill{height:100%;background:var(--gold);border-radius:5px;transition:width .3s ease;}
  .poll-option.voted{border-color:var(--gold);background:#FBF1DF;}
  .vote-btn{width:100%;text-align:left;background:var(--paper-2);border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:10px;font-size:13.5px;font-weight:600;color:var(--ink);}
  .vote-btn:hover{border-color:var(--gold);background:#FBF1DF;}
  .reply-thread{display:flex;flex-direction:column;gap:12px;margin:16px 0;}
  .concern-card{background:var(--paper-2);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin-bottom:14px;}
  .concern-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;}
  .concern-subject{font-size:15px;font-weight:700;margin:0 0 3px;}
  .concern-meta{font-size:12px;color:var(--slate);}
  .concern-msg{font-size:13.5px;line-height:1.55;color:#3B4250;margin-top:8px;}
  @media (max-width:860px){
    .app{flex-direction:column;}
    .sidebar{width:100%;flex-direction:row;flex-wrap:wrap;align-items:center;padding:16px 20px;}
    .nav,.role-switch{display:none;}
    .main{padding:24px;}
    .stats{grid-template-columns:repeat(2,1fr);}
    .tickets{grid-template-columns:1fr;}
  }
  .auth-wrap{
    min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;
    background:linear-gradient(135deg, #081A3D 0%, #0F49A7 55%, #2E7DE0 100%);
    position:relative;overflow:hidden;
  }
  .auth-wrap::before,.auth-wrap::after{
    content:'';position:absolute;border-radius:50%;filter:blur(70px);pointer-events:none;z-index:0;
  }
  .auth-wrap::before{width:420px;height:420px;background:rgba(222,174,35,0.35);top:-120px;left:-100px;}
  .auth-wrap::after{width:380px;height:380px;background:rgba(10,129,16,0.4);bottom:-110px;right:-90px;}
  .auth-heading{text-align:center;margin-bottom:26px;position:relative;z-index:1;}
  .auth-eyebrow{color:rgba(255,255,255,0.85);font-size:14px;margin:0 0 4px;font-weight:500;}
  .auth-title{color:#fff;font-size:28px;font-weight:700;margin:0;letter-spacing:0.2px;}
  .auth-card{
    background:rgba(255,255,255,0.62);
    border:1px solid rgba(255,255,255,0.55);
    border-radius:18px;width:100%;max-width:420px;padding:36px 32px 28px;
    box-shadow:0 25px 60px rgba(3,15,45,0.35), inset 0 1px 0 rgba(255,255,255,0.6);
    backdrop-filter:blur(22px) saturate(180%);
    -webkit-backdrop-filter:blur(22px) saturate(180%);
    position:relative;z-index:1;
  }
  .auth-card input,.auth-card select,.auth-card textarea{
    background:rgba(255,255,255,0.55);
    border:1px solid rgba(255,255,255,0.7);
    backdrop-filter:blur(6px);
    -webkit-backdrop-filter:blur(6px);
  }
  .auth-card input:focus,.auth-card select:focus,.auth-card textarea:focus{background:rgba(255,255,255,0.85);}
  .auth-brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:0;margin-bottom:22px;}
  .auth-logo{width:110px;height:110px;object-fit:contain;flex-shrink:0;filter:drop-shadow(0 6px 14px rgba(3,15,45,0.25));}
  .auth-foot{text-align:center;font-size:13px;color:var(--slate);margin-top:18px;}
  .role-pick{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px;}
  .role-pick label{border:1px solid rgba(255,255,255,0.7);background:rgba(255,255,255,0.4);border-radius:8px;padding:10px 6px;text-align:center;font-size:12px;font-weight:600;color:var(--slate);cursor:pointer;display:block;}
  .role-pick input{display:none;}
  .role-pick input:checked + span{color:var(--ink);}
  .role-pick label:has(input:checked){border-color:var(--gold);background:rgba(251,241,223,0.85);color:var(--ink);}
  .full-w{width:100%;}


/* =========================================================
   SBO NEXUS — Professional UI polish
   Keeps all existing modules/routes; visual-only enhancements.
   ========================================================= */
:root{
  --surface:#ffffff;
  --surface-soft:#f7faf8;
  --border-soft:#e4ebe7;
  --shadow-sm:0 4px 14px rgba(13,63,38,.05);
  --shadow-md:0 12px 30px rgba(13,63,38,.09);
  --shadow-lg:0 22px 55px rgba(13,63,38,.14);
  --ease:cubic-bezier(.22,.61,.36,1);
}
html{scroll-behavior:smooth;}
body{overflow-x:hidden;}

/* Cleaner navigation grouping */
.sidebar{
  box-shadow:0 1px 0 rgba(15,91,42,.04),0 8px 28px rgba(15,55,35,.08);
  backdrop-filter:blur(14px);
  -webkit-backdrop-filter:blur(14px);
}
.brand-link{transition:transform .25s var(--ease),opacity .25s var(--ease);}
.brand-link:hover{transform:translateY(-1px);opacity:.94;}
.nav{
  background:#f8fbf9;
  border:1px solid #e7ede9;
  border-radius:14px;
  padding:4px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.9);
}
.nav-item{
  border-radius:10px!important;
  transition:color .22s var(--ease),background .22s var(--ease),transform .22s var(--ease),box-shadow .22s var(--ease)!important;
}
.nav-item:hover{
  background:#eef7f1!important;
  color:#075b2a!important;
  transform:translateY(-1px)!important;
}
.nav-item.active{
  background:#ffffff!important;
  box-shadow:0 3px 10px rgba(13,91,42,.08)!important;
}
.nav-item.active::after{left:12px;right:12px;height:3px;bottom:2px;}
.user-area{transition:border-color .2s ease;}
.user-link{padding:6px 8px;border-radius:12px;transition:background .2s ease,transform .2s ease;}
.user-link:hover{background:#f3f7f4;transform:translateY(-1px);}
.logout-link{transition:background .2s ease,color .2s ease,transform .2s ease;}
.logout-link:hover{transform:rotate(-6deg) scale(1.04);}

/* Subtle page entrance */
.main{animation:sboPageIn .48s var(--ease) both;}
@keyframes sboPageIn{from{opacity:0;transform:translateY(7px)}to{opacity:1;transform:none}}

/* Premium hero treatment */
.welcome-banner{
  isolation:isolate;
  box-shadow:0 18px 42px rgba(0,91,42,.16),inset 0 1px 0 rgba(255,255,255,.12);
  transition:transform .35s var(--ease),box-shadow .35s var(--ease);
}
.welcome-banner:hover{transform:translateY(-2px);box-shadow:0 24px 52px rgba(0,91,42,.19),inset 0 1px 0 rgba(255,255,255,.14);}
.welcome-banner::before{
  animation:sboDots 14s linear infinite;
}
@keyframes sboDots{from{background-position:0 0,0 0}to{background-position:120px 80px,-100px 90px}}
.welcome-banner::after{transition:transform .8s var(--ease),opacity .4s ease;}
.welcome-banner:hover::after{transform:scale(1.04) rotate(1deg);opacity:.16;}
.welcome-actions .btn{transition:transform .2s var(--ease),box-shadow .2s var(--ease),background .2s ease!important;}
.welcome-actions .btn:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(0,0,0,.14);}

/* Stats — consistent hierarchy and lift */
.stat{
  position:relative;
  overflow:hidden;
  transition:transform .25s var(--ease),box-shadow .25s var(--ease),border-color .25s ease!important;
}
.stat::after{
  content:'';position:absolute;width:100px;height:100px;right:-42px;bottom:-55px;border-radius:50%;
  background:radial-gradient(circle,rgba(7,91,42,.07),transparent 68%);
  pointer-events:none;
}
.stat:hover{transform:translateY(-4px)!important;box-shadow:var(--shadow-md)!important;border-color:#d8e6de!important;}
.stat-icon{transition:transform .25s var(--ease),box-shadow .25s var(--ease);}
.stat:hover .stat-icon{transform:scale(1.06) rotate(-2deg);box-shadow:0 7px 15px rgba(0,0,0,.08);}
.stat-link{transition:letter-spacing .2s ease;}
.stat:hover .stat-link{letter-spacing:.15px;}

/* Content panels */
.detail-card,.quick-card{
  position:relative;
  overflow:hidden;
  transition:transform .28s var(--ease),box-shadow .28s var(--ease),border-color .28s ease;
}
.detail-card:hover,.quick-card:hover{box-shadow:var(--shadow-md);border-color:#dce7e1;}
.detail-card::before,.quick-card::before{
  content:'';position:absolute;left:0;top:0;width:100%;height:2px;
  background:linear-gradient(90deg,#0b6b36,rgba(21,160,77,.08),transparent);
  opacity:.7;
}
.quick-link{position:relative;overflow:hidden;transition:transform .24s var(--ease),background .24s ease,border-color .24s ease,box-shadow .24s ease!important;}
.quick-link::after{
  content:'→';position:absolute;right:14px;opacity:0;transform:translateX(-6px);color:var(--ink);font-weight:700;transition:opacity .2s ease,transform .2s ease;
}
.quick-link:hover{transform:translateX(3px)!important;box-shadow:0 7px 18px rgba(13,91,42,.07);}
.quick-link:hover::after{opacity:1;transform:none;}
.quick-icon{transition:transform .25s var(--ease),background .25s ease;}
.quick-link:hover .quick-icon{transform:scale(1.06);background:#e3f2e8;}

/* Agenda rows */
.agenda-row{transition:background .22s ease,transform .22s var(--ease),padding .22s var(--ease);}
.agenda-row:hover{transform:translateX(2px);box-shadow:inset 3px 0 0 #0b6b36;}
.agenda-date{transition:transform .25s var(--ease),box-shadow .25s ease;}
.agenda-row:hover .agenda-date{transform:translateY(-1px);box-shadow:0 7px 16px rgba(5,91,42,.14);}
.agenda-title a{transition:color .2s ease;}
.agenda-title a:hover{color:#0b7a3b!important;text-decoration:none;}

/* Buttons and interactive controls */
.btn{position:relative;overflow:hidden;transition:transform .2s var(--ease),box-shadow .2s var(--ease),background .2s ease,border-color .2s ease!important;}
.btn:hover{transform:translateY(-1px);box-shadow:0 7px 16px rgba(16,65,40,.09);}
.btn:active{transform:translateY(0) scale(.98);}
.btn-primary:hover{box-shadow:0 9px 20px rgba(11,107,54,.2);}

/* Event cards */
.ticket,.hero-card{
  transition:transform .3s var(--ease),box-shadow .3s var(--ease),border-color .3s ease!important;
}
.ticket:hover,.hero-card:hover{transform:translateY(-5px)!important;box-shadow:var(--shadow-lg)!important;}
.hero-card-media{overflow:hidden;}
.hero-card-img{transition:transform .55s var(--ease),filter .35s ease;}
.hero-card:hover .hero-card-img{transform:scale(1.035);filter:saturate(1.04);}
.ticket-date-badge,.cat-chip,.badge{transition:transform .2s var(--ease);}
.ticket:hover .ticket-date-badge{transform:translateY(-1px);}

/* Forms, tables, modal surfaces on existing modules */
input,select,textarea{transition:border-color .2s ease,box-shadow .2s ease,background .2s ease!important;}
input:focus,select:focus,textarea:focus{outline:none!important;border-color:#51a773!important;box-shadow:0 0 0 3px rgba(21,160,77,.11)!important;background:#fff!important;}
.modal{animation:sboModalIn .25s var(--ease) both;}
@keyframes sboModalIn{from{opacity:0;transform:translateY(10px) scale(.985)}to{opacity:1;transform:none}}

/* Reduced motion accessibility */
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto;}
  *,*::before,*::after{animation-duration:.001ms!important;animation-iteration-count:1!important;transition-duration:.001ms!important;}
}

/* Keep the navigation usable at smaller desktop widths */
@media (max-width:1180px){
  .nav{overflow-x:auto;justify-content:flex-start;scrollbar-width:none;}
  .nav::-webkit-scrollbar{display:none;}
  .nav-item{flex:0 0 auto;}
}


  /* ===== Polls & Surveys refresh ===== */
  .polls-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:22px;padding:28px 30px;margin-bottom:16px;border:1px solid #DDE6E1;border-radius:18px;background:linear-gradient(135deg,#F7FBF8 0%,#FFFFFF 58%,#FFF9EA 100%);box-shadow:0 12px 30px rgba(20,33,61,.06);}
  .polls-hero:before,.polls-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none;}
  .polls-hero:before{width:180px;height:180px;right:45px;top:-100px;background:rgba(31,139,76,.08);}
  .polls-hero:after{width:120px;height:120px;right:-30px;bottom:-75px;background:rgba(239,178,61,.12);}
  .polls-kicker{font-size:10px;font-weight:800;letter-spacing:1.7px;color:#1F8B4C;margin-bottom:7px;}
  .polls-hero h2{margin:0;font-size:28px;letter-spacing:-.5px;color:var(--ink);}
  .polls-hero .sub{max-width:650px;margin:7px 0 0;line-height:1.6;}
  .polls-hero-icon{position:relative;z-index:1;display:grid;place-items:center;width:68px;height:68px;border-radius:20px;background:#fff;border:1px solid #E5EAE6;box-shadow:0 10px 25px rgba(20,33,61,.08);font-size:30px;color:#EFB23D;animation:pollFloat 3.5s ease-in-out infinite;}
  @keyframes pollFloat{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-5px) rotate(4deg)}}
  .poll-role-note{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:11px 14px;border-radius:12px;background:#fff;border:1px solid var(--line);font-size:12.5px;color:var(--slate);}
  .role-chip{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;}
  .student-chip{background:#EAF7EF;color:#1F8B4C}.officer-chip{background:#FFF3D8;color:#A46B00}.admin-chip{background:#EEEAFE;color:#6652B5}
  .poll-create-btn{box-shadow:0 8px 18px rgba(31,139,76,.16);transition:transform .2s ease,box-shadow .2s ease}.poll-create-btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(31,139,76,.22)}
  .poll-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
  .poll-card{position:relative;overflow:hidden;min-height:210px;padding:21px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(145deg,#fff 0%,#FBFCFB 100%);box-shadow:0 8px 22px rgba(20,33,61,.055);transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease;}
  .poll-card:hover{transform:translateY(-5px);border-color:#C9D9CF;box-shadow:0 16px 32px rgba(20,33,61,.11)}
  .poll-card-glow{position:absolute;width:130px;height:130px;right:-70px;top:-70px;border-radius:50%;background:rgba(31,139,76,.06);transition:transform .35s ease,background .35s ease}.poll-card:hover .poll-card-glow{transform:scale(1.35);background:rgba(239,178,61,.10)}
  .poll-card-top{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;margin-bottom:17px}.poll-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.3px}.poll-badge span{width:6px;height:6px;border-radius:50%;background:currentColor}.poll-badge.is-open{background:#EAF7EF;color:#1F8B4C}.poll-badge.is-closed{background:#F1F2F4;color:#6E7681}.poll-arrow{font-size:20px;color:#9AA3AD;transition:transform .22s ease,color .22s ease}.poll-card:hover .poll-arrow{transform:translateX(4px);color:#1F8B4C}
  .poll-card h3{position:relative;z-index:1;margin:0;font-size:18px;line-height:1.35;color:var(--ink);letter-spacing:-.2px}.poll-card-desc{position:relative;z-index:1;margin:9px 0 0;font-size:12.5px;line-height:1.55;color:var(--slate);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.poll-card-meta{position:relative;z-index:1;display:flex;gap:7px;flex-wrap:wrap;margin-top:15px;font-size:11px;color:#7C8590}.poll-card-footer{position:relative;z-index:1;margin-top:18px;padding-top:13px;border-top:1px solid #EEF1EF}.poll-status-text{font-size:11.5px;font-weight:800}.voted-text{color:#1F8B4C}.vote-now-text{color:#A46B00}.manage-text{color:#6652B5}
  .poll-empty{padding:55px 20px;text-align:center;border:1px dashed #CAD5CE;border-radius:16px;background:#FBFCFB}.poll-empty-icon{font-size:32px;color:#C3CEC6;margin-bottom:8px}.poll-empty .big{font-weight:800;font-size:17px}.poll-empty p{font-size:12.5px;color:var(--slate);margin:7px 0 0}
  .poll-modal{position:relative;overflow:hidden}.poll-modal-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:#FFF5D9;color:#C78C20;font-size:21px;margin-bottom:10px}.poll-modal h3{margin-bottom:5px}.poll-modal .field label span{font-weight:500;color:var(--slate)}
  .poll-detail-card{position:relative;overflow:hidden}.poll-detail-card:before{content:"";position:absolute;right:-70px;top:-80px;width:190px;height:190px;border-radius:50%;background:rgba(31,139,76,.055);pointer-events:none}.poll-detail-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.poll-detail-kicker{font-size:10px;letter-spacing:1.5px;font-weight:800;color:#1F8B4C;margin-bottom:5px}.poll-detail-card .detail-desc{position:relative;padding:12px 14px;border-radius:11px;background:#F8FAF9;border-left:3px solid #EFB23D}.poll-option{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:12px;padding:14px 15px;margin-bottom:11px;background:#fff;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.poll-option:hover{transform:translateX(3px);border-color:#C7D7CC;box-shadow:0 7px 18px rgba(20,33,61,.06)}.poll-option.voted{border-color:#E4B64D;background:linear-gradient(90deg,#FFF9EA,#fff)}.poll-bar{height:9px;background:#EEF2EF;border-radius:99px;overflow:hidden}.poll-bar-fill{height:100%;background:linear-gradient(90deg,#1F8B4C,#48A96D,#EFB23D);background-size:200% 100%;border-radius:99px;animation:pollBarShimmer 2.8s linear infinite}.poll-option.voted .poll-bar-fill{box-shadow:0 0 10px rgba(239,178,61,.28)}@keyframes pollBarShimmer{0%{background-position:0% 0}100%{background-position:200% 0}}
  .vote-form{margin:0 0 10px}.vote-btn{display:flex;align-items:center;gap:12px;width:100%;text-align:left;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px 16px;margin:0;font-size:14px;font-weight:700;color:var(--ink);cursor:pointer;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease}.vote-btn:hover{transform:translateY(-2px);border-color:#9FC0AA;background:#F8FCF9;box-shadow:0 9px 20px rgba(20,33,61,.08)}.vote-btn:active{transform:translateY(0)}.vote-radio{width:17px;height:17px;border:2px solid #B9C5BD;border-radius:50%;flex:0 0 auto;transition:border-color .2s ease,box-shadow .2s ease}.vote-btn:hover .vote-radio{border-color:#1F8B4C;box-shadow:0 0 0 4px rgba(31,139,76,.09)}.vote-chevron{margin-left:auto;color:#A4ADA6;transition:transform .2s ease,color .2s ease}.vote-btn:hover .vote-chevron{transform:translateX(4px);color:#1F8B4C}.poll-hint{margin:14px 0 0;padding:10px 12px;border-radius:10px;background:#F7F9F8;color:var(--slate);font-size:11.5px}
  @media (max-width:760px){.poll-grid{grid-template-columns:1fr}.polls-hero{padding:22px}.polls-hero-icon{width:55px;height:55px;font-size:24px}.poll-detail-head{flex-direction:column}.poll-detail-head>.badge{align-self:flex-start}}
  @media (prefers-reduced-motion:reduce){.polls-hero-icon,.poll-bar-fill{animation:none}.poll-card,.vote-btn,.poll-option{transition:none}}


  /* ===== NBSC Green Archers theme for Polls & Surveys ===== */
  .polls-hero{
    border:1px solid rgba(6,92,45,.18);
    background:
      radial-gradient(circle at 86% 18%, rgba(215,169,40,.20) 0 2px, transparent 3px),
      radial-gradient(circle at 76% 72%, rgba(255,255,255,.55) 0 3px, transparent 4px),
      linear-gradient(135deg,#EAF7EE 0%,#F8FCF9 52%,#E7F2EA 100%);
    box-shadow:0 16px 38px rgba(6,76,37,.10), inset 0 1px 0 rgba(255,255,255,.85);
  }
  .polls-hero:before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    right:-90px;top:-115px;
    border:2px solid rgba(6,92,45,.12);
    border-radius:50%;
    box-shadow:
      0 0 0 22px rgba(6,92,45,.045),
      0 0 0 44px rgba(215,169,40,.055),
      0 0 0 66px rgba(6,92,45,.035);
    transform:rotate(-18deg);
    pointer-events:none;
  }
  .polls-hero:after{
    content:'';
    position:absolute;
    left:-35px;bottom:-72px;
    width:210px;height:140px;
    background:
      repeating-linear-gradient(135deg,
        rgba(6,92,45,.08) 0 2px,
        transparent 2px 15px);
    transform:skewX(-18deg);
    opacity:.7;
    pointer-events:none;
  }
  .polls-hero > *{position:relative;z-index:2;}
  .polls-hero .eyebrow,
  .polls-hero .hero-eyebrow{
    color:#0B7A3B !important;
    letter-spacing:1.2px;
    font-weight:800;
  }
  .polls-hero h1,.polls-hero h2,.polls-hero h3{
    color:#064C25;
  }
  .polls-hero p{color:#4E6657;}
  .polls-hero .btn-primary,
  .polls-hero .btn-green{
    background:linear-gradient(135deg,#075B2A,#0B7A3B);
    border-color:#075B2A;
    color:#fff;
    box-shadow:0 7px 18px rgba(7,91,42,.22);
  }
  .polls-hero .btn-primary:hover,
  .polls-hero .btn-green:hover{
    background:linear-gradient(135deg,#064C25,#096B34);
    transform:translateY(-1px);
  }
  .poll-option{
    border-color:rgba(7,91,42,.14);
    background:rgba(255,255,255,.78);
    box-shadow:0 5px 16px rgba(6,76,37,.055);
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }
  .poll-option:hover{
    transform:translateY(-2px);
    border-color:rgba(7,91,42,.32);
    box-shadow:0 9px 22px rgba(6,76,37,.10);
  }
  .poll-option.voted{
    border-color:#0B7A3B;
    background:#EDF8F0;
    box-shadow:0 7px 18px rgba(7,91,42,.10);
  }
  .poll-bar{background:#DCEBE0;}
  .poll-bar-fill{
    background:linear-gradient(90deg,#064C25,#0B7A3B 72%,#D7A928);
    box-shadow:0 1px 5px rgba(7,91,42,.18);
  }
  .vote-btn{
    border-color:rgba(7,91,42,.15);
    background:rgba(255,255,255,.84);
  }
  .vote-btn:hover{
    border-color:#0B7A3B;
    background:#EDF8F0;
    color:#064C25;
  }
  .poll-stat,.poll-card,.survey-card{
    border-color:rgba(7,91,42,.14);
    box-shadow:0 9px 24px rgba(6,76,37,.07);
  }
  .poll-status-active,.survey-status-active{
    background:#E7F6EB !important;
    color:#075B2A !important;
    border-color:#BFE3C8 !important;
  }
  .polls-page .card,
  .polls-page .panel{
    border-color:rgba(7,91,42,.13);
  }
  @media (prefers-reduced-motion:no-preference){
    .polls-hero:before{
      animation:nbsc-archer-glow 8s ease-in-out infinite alternate;
    }
    @keyframes nbsc-archer-glow{
      from{transform:rotate(-18deg) scale(1);}
      to{transform:rotate(-12deg) scale(1.04);}
    }
  }



/* Officer dashboard — professional student-style layout */
.officer-welcome-banner{margin-bottom:18px;}
.officer-welcome-banner .officer-ghost-btn{background:rgba(255,255,255,.10);color:#fff;border-color:rgba(255,255,255,.18);}
.officer-welcome-banner .officer-ghost-btn:hover{background:rgba(255,255,255,.18);color:#fff;}
.officer-dashboard-shell{grid-template-columns:minmax(0,1fr) 300px;gap:18px;}
.officer-dashboard-side{display:flex;flex-direction:column;gap:18px;}
.officer-dashboard-side .quick-card{margin:0;}
.officer-dashboard-side .quick-card + .quick-card{margin-top:0;}
@media (max-width:1100px){.officer-dashboard-shell{grid-template-columns:1fr}.officer-dashboard-side{display:grid;grid-template-columns:1fr 1fr;gap:16px}.officer-dashboard-side .quick-card{height:100%;}}
@media (max-width:620px){.officer-dashboard-side{display:block}.officer-dashboard-side .quick-card + .quick-card{margin-top:14px;}.officer-welcome-banner{margin-bottom:16px;}}


/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* Professional dashboard refresh */
.dashboard-shell{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start;}
.dashboard-main,.dashboard-side{min-width:0;}
.quick-card{background:linear-gradient(145deg,#fff,#F7FBF8);border:1px solid #E0EAE4;border-radius:16px;padding:18px;box-shadow:0 10px 24px rgba(4,72,34,.05);}
.quick-card h3{margin:0 0 14px;font-size:15px;}
.quick-link{display:flex;align-items:center;gap:12px;padding:11px;border-radius:12px;background:#F5F9F6;border:1px solid #E7EEE9;margin-bottom:9px;font-size:13px;font-weight:600;transition:.15s ease;}
.quick-link:hover{background:#EEF7F1;border-color:#CFE1D4;transform:translateY(-1px);}
.quick-icon{width:34px;height:34px;border-radius:10px;background:#E1F0E6;color:var(--ink);display:grid;place-items:center;flex-shrink:0;}
.dashboard-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#BDE3CA;font-weight:700;margin-bottom:8px;}
.welcome-actions{display:flex;gap:9px;margin-top:18px;position:relative;z-index:1;flex-wrap:wrap;}
.welcome-actions .btn{border-color:rgba(255,255,255,.22);}
@media (max-width:1100px){.dashboard-shell{grid-template-columns:1fr}.dashboard-side{display:grid;grid-template-columns:1fr 1fr;gap:16px}.dashboard-side .quick-card{height:100%;}}
@media (max-width:860px){.main{padding:22px 18px}.stats{grid-template-columns:repeat(2,minmax(0,1fr));}.dashboard-side{grid-template-columns:1fr}.welcome-banner{padding:24px}.topbar h2{font-size:24px;}}
@media (max-width:520px){.stats{grid-template-columns:1fr}.stat{padding:16px}.welcome-banner h2{font-size:22px}.detail-card{padding:17px}.agenda-row{gap:10px}.agenda-date{width:54px}.sidebar{padding:14px 16px}}

  /* ===== Polls & Surveys refresh ===== */
  .polls-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:22px;padding:28px 30px;margin-bottom:16px;border:1px solid #DDE6E1;border-radius:18px;background:linear-gradient(135deg,#F7FBF8 0%,#FFFFFF 58%,#FFF9EA 100%);box-shadow:0 12px 30px rgba(20,33,61,.06);}
  .polls-hero:before,.polls-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none;}
  .polls-hero:before{width:180px;height:180px;right:45px;top:-100px;background:rgba(31,139,76,.08);}
  .polls-hero:after{width:120px;height:120px;right:-30px;bottom:-75px;background:rgba(239,178,61,.12);}
  .polls-kicker{font-size:10px;font-weight:800;letter-spacing:1.7px;color:#1F8B4C;margin-bottom:7px;}
  .polls-hero h2{margin:0;font-size:28px;letter-spacing:-.5px;color:var(--ink);}
  .polls-hero .sub{max-width:650px;margin:7px 0 0;line-height:1.6;}
  .polls-hero-icon{position:relative;z-index:1;display:grid;place-items:center;width:68px;height:68px;border-radius:20px;background:#fff;border:1px solid #E5EAE6;box-shadow:0 10px 25px rgba(20,33,61,.08);font-size:30px;color:#EFB23D;animation:pollFloat 3.5s ease-in-out infinite;}
  @keyframes pollFloat{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-5px) rotate(4deg)}}
  .poll-role-note{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:11px 14px;border-radius:12px;background:#fff;border:1px solid var(--line);font-size:12.5px;color:var(--slate);}
  .role-chip{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;}
  .student-chip{background:#EAF7EF;color:#1F8B4C}.officer-chip{background:#FFF3D8;color:#A46B00}.admin-chip{background:#EEEAFE;color:#6652B5}
  .poll-create-btn{box-shadow:0 8px 18px rgba(31,139,76,.16);transition:transform .2s ease,box-shadow .2s ease}.poll-create-btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(31,139,76,.22)}
  .poll-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
  .poll-card{position:relative;overflow:hidden;min-height:210px;padding:21px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(145deg,#fff 0%,#FBFCFB 100%);box-shadow:0 8px 22px rgba(20,33,61,.055);transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease;}
  .poll-card:hover{transform:translateY(-5px);border-color:#C9D9CF;box-shadow:0 16px 32px rgba(20,33,61,.11)}
  .poll-card-glow{position:absolute;width:130px;height:130px;right:-70px;top:-70px;border-radius:50%;background:rgba(31,139,76,.06);transition:transform .35s ease,background .35s ease}.poll-card:hover .poll-card-glow{transform:scale(1.35);background:rgba(239,178,61,.10)}
  .poll-card-top{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;margin-bottom:17px}.poll-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.3px}.poll-badge span{width:6px;height:6px;border-radius:50%;background:currentColor}.poll-badge.is-open{background:#EAF7EF;color:#1F8B4C}.poll-badge.is-closed{background:#F1F2F4;color:#6E7681}.poll-arrow{font-size:20px;color:#9AA3AD;transition:transform .22s ease,color .22s ease}.poll-card:hover .poll-arrow{transform:translateX(4px);color:#1F8B4C}
  .poll-card h3{position:relative;z-index:1;margin:0;font-size:18px;line-height:1.35;color:var(--ink);letter-spacing:-.2px}.poll-card-desc{position:relative;z-index:1;margin:9px 0 0;font-size:12.5px;line-height:1.55;color:var(--slate);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.poll-card-meta{position:relative;z-index:1;display:flex;gap:7px;flex-wrap:wrap;margin-top:15px;font-size:11px;color:#7C8590}.poll-card-footer{position:relative;z-index:1;margin-top:18px;padding-top:13px;border-top:1px solid #EEF1EF}.poll-status-text{font-size:11.5px;font-weight:800}.voted-text{color:#1F8B4C}.vote-now-text{color:#A46B00}.manage-text{color:#6652B5}
  .poll-empty{padding:55px 20px;text-align:center;border:1px dashed #CAD5CE;border-radius:16px;background:#FBFCFB}.poll-empty-icon{font-size:32px;color:#C3CEC6;margin-bottom:8px}.poll-empty .big{font-weight:800;font-size:17px}.poll-empty p{font-size:12.5px;color:var(--slate);margin:7px 0 0}
  .poll-modal{position:relative;overflow:hidden}.poll-modal-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:#FFF5D9;color:#C78C20;font-size:21px;margin-bottom:10px}.poll-modal h3{margin-bottom:5px}.poll-modal .field label span{font-weight:500;color:var(--slate)}
  .poll-detail-card{position:relative;overflow:hidden}.poll-detail-card:before{content:"";position:absolute;right:-70px;top:-80px;width:190px;height:190px;border-radius:50%;background:rgba(31,139,76,.055);pointer-events:none}.poll-detail-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.poll-detail-kicker{font-size:10px;letter-spacing:1.5px;font-weight:800;color:#1F8B4C;margin-bottom:5px}.poll-detail-card .detail-desc{position:relative;padding:12px 14px;border-radius:11px;background:#F8FAF9;border-left:3px solid #EFB23D}.poll-option{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:12px;padding:14px 15px;margin-bottom:11px;background:#fff;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.poll-option:hover{transform:translateX(3px);border-color:#C7D7CC;box-shadow:0 7px 18px rgba(20,33,61,.06)}.poll-option.voted{border-color:#E4B64D;background:linear-gradient(90deg,#FFF9EA,#fff)}.poll-bar{height:9px;background:#EEF2EF;border-radius:99px;overflow:hidden}.poll-bar-fill{height:100%;background:linear-gradient(90deg,#1F8B4C,#48A96D,#EFB23D);background-size:200% 100%;border-radius:99px;animation:pollBarShimmer 2.8s linear infinite}.poll-option.voted .poll-bar-fill{box-shadow:0 0 10px rgba(239,178,61,.28)}@keyframes pollBarShimmer{0%{background-position:0% 0}100%{background-position:200% 0}}
  .vote-form{margin:0 0 10px}.vote-btn{display:flex;align-items:center;gap:12px;width:100%;text-align:left;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px 16px;margin:0;font-size:14px;font-weight:700;color:var(--ink);cursor:pointer;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease}.vote-btn:hover{transform:translateY(-2px);border-color:#9FC0AA;background:#F8FCF9;box-shadow:0 9px 20px rgba(20,33,61,.08)}.vote-btn:active{transform:translateY(0)}.vote-radio{width:17px;height:17px;border:2px solid #B9C5BD;border-radius:50%;flex:0 0 auto;transition:border-color .2s ease,box-shadow .2s ease}.vote-btn:hover .vote-radio{border-color:#1F8B4C;box-shadow:0 0 0 4px rgba(31,139,76,.09)}.vote-chevron{margin-left:auto;color:#A4ADA6;transition:transform .2s ease,color .2s ease}.vote-btn:hover .vote-chevron{transform:translateX(4px);color:#1F8B4C}.poll-hint{margin:14px 0 0;padding:10px 12px;border-radius:10px;background:#F7F9F8;color:var(--slate);font-size:11.5px}
  @media (max-width:760px){.poll-grid{grid-template-columns:1fr}.polls-hero{padding:22px}.polls-hero-icon{width:55px;height:55px;font-size:24px}.poll-detail-head{flex-direction:column}.poll-detail-head>.badge{align-self:flex-start}}
  @media (prefers-reduced-motion:reduce){.polls-hero-icon,.poll-bar-fill{animation:none}.poll-card,.vote-btn,.poll-option{transition:none}}


  /* ===== NBSC Green Archers theme for Polls & Surveys ===== */
  .polls-hero{
    border:1px solid rgba(6,92,45,.18);
    background:
      radial-gradient(circle at 86% 18%, rgba(215,169,40,.20) 0 2px, transparent 3px),
      radial-gradient(circle at 76% 72%, rgba(255,255,255,.55) 0 3px, transparent 4px),
      linear-gradient(135deg,#EAF7EE 0%,#F8FCF9 52%,#E7F2EA 100%);
    box-shadow:0 16px 38px rgba(6,76,37,.10), inset 0 1px 0 rgba(255,255,255,.85);
  }
  .polls-hero:before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    right:-90px;top:-115px;
    border:2px solid rgba(6,92,45,.12);
    border-radius:50%;
    box-shadow:
      0 0 0 22px rgba(6,92,45,.045),
      0 0 0 44px rgba(215,169,40,.055),
      0 0 0 66px rgba(6,92,45,.035);
    transform:rotate(-18deg);
    pointer-events:none;
  }
  .polls-hero:after{
    content:'';
    position:absolute;
    left:-35px;bottom:-72px;
    width:210px;height:140px;
    background:
      repeating-linear-gradient(135deg,
        rgba(6,92,45,.08) 0 2px,
        transparent 2px 15px);
    transform:skewX(-18deg);
    opacity:.7;
    pointer-events:none;
  }
  .polls-hero > *{position:relative;z-index:2;}
  .polls-hero .eyebrow,
  .polls-hero .hero-eyebrow{
    color:#0B7A3B !important;
    letter-spacing:1.2px;
    font-weight:800;
  }
  .polls-hero h1,.polls-hero h2,.polls-hero h3{
    color:#064C25;
  }
  .polls-hero p{color:#4E6657;}
  .polls-hero .btn-primary,
  .polls-hero .btn-green{
    background:linear-gradient(135deg,#075B2A,#0B7A3B);
    border-color:#075B2A;
    color:#fff;
    box-shadow:0 7px 18px rgba(7,91,42,.22);
  }
  .polls-hero .btn-primary:hover,
  .polls-hero .btn-green:hover{
    background:linear-gradient(135deg,#064C25,#096B34);
    transform:translateY(-1px);
  }
  .poll-option{
    border-color:rgba(7,91,42,.14);
    background:rgba(255,255,255,.78);
    box-shadow:0 5px 16px rgba(6,76,37,.055);
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }
  .poll-option:hover{
    transform:translateY(-2px);
    border-color:rgba(7,91,42,.32);
    box-shadow:0 9px 22px rgba(6,76,37,.10);
  }
  .poll-option.voted{
    border-color:#0B7A3B;
    background:#EDF8F0;
    box-shadow:0 7px 18px rgba(7,91,42,.10);
  }
  .poll-bar{background:#DCEBE0;}
  .poll-bar-fill{
    background:linear-gradient(90deg,#064C25,#0B7A3B 72%,#D7A928);
    box-shadow:0 1px 5px rgba(7,91,42,.18);
  }
  .vote-btn{
    border-color:rgba(7,91,42,.15);
    background:rgba(255,255,255,.84);
  }
  .vote-btn:hover{
    border-color:#0B7A3B;
    background:#EDF8F0;
    color:#064C25;
  }
  .poll-stat,.poll-card,.survey-card{
    border-color:rgba(7,91,42,.14);
    box-shadow:0 9px 24px rgba(6,76,37,.07);
  }
  .poll-status-active,.survey-status-active{
    background:#E7F6EB !important;
    color:#075B2A !important;
    border-color:#BFE3C8 !important;
  }
  .polls-page .card,
  .polls-page .panel{
    border-color:rgba(7,91,42,.13);
  }
  @media (prefers-reduced-motion:no-preference){
    .polls-hero:before{
      animation:nbsc-archer-glow 8s ease-in-out infinite alternate;
    }
    @keyframes nbsc-archer-glow{
      from{transform:rotate(-18deg) scale(1);}
      to{transform:rotate(-12deg) scale(1.04);}
    }
  }


/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* ============================================================
   SBO NEXUS — reference-style interface refresh
   Visual/layout only: existing modules, routes and functionality stay intact.
   ============================================================ */
:root{
  --ink:#0B6B36;
  --ink-2:#148747;
  --paper:#F6F8F7;
  --paper-2:#FFFFFF;
  --gold:#F2B72B;
  --gold-dark:#8A6510;
  --blue:#2E63B8;
  --blue-light:#E7EFFC;
  --purple:#7651B8;
  --slate:#66707A;
  --slate-light:#EEF1EF;
  --line:#E2E7E4;
}
body{background:#F7F9F8;color:#1F2623;}
.app{display:block;min-height:100vh;}

/* Top navigation */
.sidebar{
  position:sticky;top:0;z-index:100;
  width:100%;height:80px;min-height:80px;
  padding:0 38px;
  background:rgba(255,255,255,.97);
  color:#1F2623;
  border-bottom:1px solid #E5EAE7;
  box-shadow:0 3px 18px rgba(15,55,35,.06);
  display:flex;flex-direction:row;align-items:center;gap:24px;
}
.brand{width:250px;flex:0 0 250px;}
.brand-link{display:flex;align-items:center;gap:11px;min-width:0;}
.brand-logo{width:42px;height:48px;padding:0;border-radius:0;background:transparent;box-shadow:none;object-fit:contain;}
.brand-copy{display:flex;flex-direction:column;line-height:1.05;min-width:0;}
.brand-copy strong{font-family:'Space Grotesk',sans-serif;font-size:20px;letter-spacing:.3px;color:#075B2A;}
.brand-copy small{font-size:10.5px;letter-spacing:.3px;color:#3E4743;margin-top:4px;white-space:nowrap;}
.nav{display:flex;flex-direction:row;align-items:stretch;justify-content:center;gap:3px;flex:1;height:100%;min-width:0;overflow:hidden;}
.nav-item{
  position:relative;flex:0 0 auto;width:auto;height:100%;
  padding:0 15px;border:none;border-radius:0;
  color:#232A27;background:transparent;
  font-size:13px;font-weight:500;
  display:flex;align-items:center;justify-content:center;gap:8px;
  white-space:nowrap;transition:color .15s ease,background .15s ease;
}
.nav-item svg{width:19px;height:19px;stroke-width:1.7;}
.nav-item:hover{background:#F4F8F5;border:none;transform:none;color:var(--ink);}
.nav-item.active{background:#F4F8F5;color:var(--ink);font-weight:700;border:none;box-shadow:none;}
.nav-item.active::after{content:'';position:absolute;left:9px;right:9px;bottom:0;height:3px;background:var(--ink);border-radius:3px 3px 0 0;}
.nav-badge{position:absolute;top:15px;right:5px;margin:0;min-width:18px;text-align:center;background:#16834A;color:#fff;font-size:9px;padding:2px 5px;border-radius:20px;font-weight:700;}
.user-area{display:flex;align-items:center;gap:9px;flex:0 0 auto;border-left:1px solid #E5EAE7;padding-left:20px;}
.user-link{display:flex;align-items:center;gap:10px;min-width:0;}
.user-copy{display:flex;flex-direction:column;line-height:1.15;min-width:105px;}
.user-copy strong{font-size:12.5px;color:#202622;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;}
.user-copy small{font-size:10.5px;color:#77807B;margin-top:3px;}
.logout-link{width:30px;height:30px;border-radius:50%;display:grid;place-items:center;color:#6B746F;}
.logout-link:hover{background:#F1F5F2;color:var(--ink);}
.logout-link svg{width:17px;height:17px;}

/* Page surface */
.main{
  width:100%;max-width:1540px;margin:0 auto;
  padding:28px 38px 34px;
  background:#F7F9F8;
  min-height:calc(100vh - 80px);
}
.main::before,.main::after{display:none;}
.main > *{position:relative;z-index:1;}
.flash{border-radius:10px;margin-bottom:16px;}

/* Dashboard hero */
.welcome-banner{
  min-height:244px;
  padding:30px 40px;
  margin-bottom:22px;
  border-radius:16px;
  background:linear-gradient(90deg,#005A2A 0%,#08753A 52%,#166F4B 100%);
  box-shadow:none;
  display:flex;flex-direction:column;justify-content:center;
  overflow:hidden;
}
.welcome-banner::before{
  opacity:.08;
  background-image:radial-gradient(circle at 80% 35%,rgba(255,255,255,.8) 0 1px,transparent 1.5px),radial-gradient(circle at 68% 70%,rgba(255,255,255,.7) 0 1px,transparent 1.5px);
  background-size:28px 28px,36px 36px;
}
.welcome-banner::after{
  content:'';position:absolute;right:30px;top:-35px;width:300px;height:300px;
  background:url('assets/nbsc-logo.png') center/contain no-repeat;
  opacity:.12;filter:grayscale(1) brightness(2.2);
  pointer-events:none;
}
.dashboard-kicker{display:none;}
.welcome-banner h2{font-size:31px;line-height:1.1;margin:0 0 10px;letter-spacing:-.5px;}
.welcome-banner p{font-size:15px;line-height:1.55;max-width:560px;color:rgba(255,255,255,.9);}
.welcome-actions{margin-top:22px;gap:12px;}
.welcome-actions .btn{min-height:45px;padding:10px 20px;border-radius:9px;}
.welcome-actions .btn-gold{background:#15A04D;border-color:#15A04D;color:#fff;}
.welcome-actions .btn-gold:hover{background:#18B158;border-color:#18B158;}
.welcome-actions .btn:not(.btn-gold){background:transparent!important;border:1px solid rgba(255,255,255,.85)!important;color:#fff!important;}

/* Dashboard statistic cards */
.stats{grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px;}
.stat{
  min-height:120px;padding:18px 20px;
  border:1px solid #E6EBE8;border-radius:15px;
  background:#fff;box-shadow:0 5px 18px rgba(22,60,42,.055);
  backdrop-filter:none;-webkit-backdrop-filter:none;
}
.stat:hover{box-shadow:0 8px 22px rgba(22,60,42,.09);transform:translateY(-1px);}
.stat-top{gap:14px;}
.stat-icon{width:50px;height:50px;border-radius:12px;}
.stat-icon.i-green{background:#61C94A;color:#fff;}
.stat-icon.i-gold{background:#FFC329;color:#fff;}
.stat-icon.i-blue{background:#3D6EC7;color:#fff;}
.stat-icon.i-purple{background:#7B56B8;color:#fff;}
.stat .n{font-size:31px;color:#0A5530;}
.stat .l{font-size:13px;color:#242B27;margin-top:4px;}
.stat-link{font-size:11.5px;color:#278B45;margin-top:10px;}

/* Dashboard content cards */
.dashboard-shell{grid-template-columns:minmax(0,1.45fr) minmax(300px,.8fr);gap:18px;}
.detail-card,.quick-card{
  background:#fff;border:1px solid #E5EAE7;border-radius:15px;
  box-shadow:0 5px 18px rgba(22,60,42,.045);
}
.detail-card{padding:20px;}
.quick-card{padding:19px;}
.quick-card h3{font-size:17px;margin-bottom:14px;color:#1D2521;}
.quick-link{background:#FAFCFB;border:1px solid #E6ECE8;border-radius:11px;margin-bottom:10px;padding:12px;font-size:12.5px;color:#252C28;}
.quick-link:hover{background:#F4FAF6;border-color:#D6E5DB;transform:none;}
.quick-icon{width:38px;height:38px;border-radius:10px;background:#F0F7F2;color:var(--ink);}
.dashboard-side .quick-card + .quick-card{margin-top:18px;}

.agenda-row{padding:14px 0;border-bottom:1px solid #E9EEEB;}
.agenda-row:hover{background:#FAFCFB;border-radius:10px;padding-left:8px;padding-right:8px;}
.agenda-date{width:58px;border-radius:10px;padding:7px 4px;background:#08733A;box-shadow:none;}
.agenda-date .am{color:#BCE6C7;}
.agenda-date .ad{font-size:18px;}
.agenda-title{font-size:14px;color:#222925;}
.agenda-meta{font-size:11.5px;}

/* Other existing pages keep their modules, but use the same clean shell */
.topbar{margin-bottom:18px;}
.topbar h2{font-size:26px;color:#164E31;}
.topbar .sub{font-size:13px;}
.section-title{color:#164E31;}
.btn{border-radius:9px;}
.btn-primary{background:#0B6B36;border-color:#0B6B36;}
.btn-primary:hover{background:#148747;border-color:#148747;}

@media (max-width:1180px){
  .sidebar{padding:0 22px;gap:14px;}
  .brand{flex-basis:220px;width:220px;}
  .nav-item{padding:0 10px;font-size:12px;}
  .user-copy{display:none;}
  .user-area{padding-left:12px;}
  .stats{grid-template-columns:repeat(2,minmax(0,1fr));}
}
@media (max-width:900px){
  .sidebar{position:relative;height:auto;min-height:72px;padding:10px 16px;flex-wrap:wrap;}
  .brand{width:auto;flex:1 1 auto;}
  .nav{order:3;width:100%;height:52px;overflow-x:auto;justify-content:flex-start;flex:none;}
  .nav-item{height:52px;flex:none;padding:0 13px;}
  .user-area{border-left:0;}
  .main{padding:22px 18px 30px;}
  .dashboard-shell{grid-template-columns:1fr;}
}
@media (max-width:620px){
  .brand-copy strong{font-size:17px;}
  .brand-copy small{font-size:9px;}
  .brand-logo{width:36px;height:40px;}
  .stats{grid-template-columns:1fr;}
  .welcome-banner{min-height:260px;padding:26px 24px;}
  .welcome-banner h2{font-size:25px;}
  .welcome-banner p{font-size:13.5px;max-width:100%;}
  .welcome-banner::after{right:-65px;width:240px;height:240px;}
  .dashboard-side{display:block;}
}

/* Connected-module navigation and contextual actions */
.topbar-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.topbar-actions .btn svg{margin-right:2px;}
.nav-item:focus-visible,.user-link:focus-visible,.logout-link:focus-visible,.btn:focus-visible{outline:3px solid rgba(20,135,71,.22);outline-offset:2px;}
@media (max-width:1180px){.nav{justify-content:flex-start;overflow-x:auto;scrollbar-width:none}.nav::-webkit-scrollbar{display:none}.nav-item{padding:0 9px}.nav-item span{font-size:11.5px}}
@media (max-width:900px){.topbar-actions{width:100%}}

/* Panel navigation: modules live inside the profile-side panel, matching the SIS-style pattern. */
.panel-user-area{display:flex;align-items:center;gap:10px;flex:0 0 auto;margin-left:0;border-left:0;padding-left:0;position:relative;}
.panel-dropdown-wrap{position:relative;}
.panel-trigger{height:42px;padding:0 9px;border:0;background:transparent;color:#33403A;font:600 13px/1 'Inter',sans-serif;display:flex;align-items:center;gap:6px;cursor:pointer;border-radius:9px;transition:background .18s ease,color .18s ease;}
.panel-trigger:hover,.panel-trigger[aria-expanded="true"]{background:#F2F7F3;color:#075B2A;}
.panel-chevron{width:14px;height:14px;transition:transform .18s ease;}
.panel-trigger[aria-expanded="true"] .panel-chevron{transform:rotate(180deg);}
.panel-menu{position:absolute;right:0;top:calc(100% + 10px);width:270px;padding:8px;background:#fff;border:1px solid #DCE5DF;border-radius:11px;box-shadow:0 18px 42px rgba(25,55,40,.16);opacity:0;visibility:hidden;transform:translateY(-6px) scale(.98);transform-origin:top right;transition:opacity .16s ease,transform .16s ease,visibility .16s ease;z-index:500;}
.panel-menu.open{opacity:1;visibility:visible;transform:translateY(0) scale(1);}
.panel-menu::before{content:'';position:absolute;right:48px;top:-6px;width:11px;height:11px;background:#fff;border-left:1px solid #DCE5DF;border-top:1px solid #DCE5DF;transform:rotate(45deg);}
.panel-menu-item{position:relative;display:flex;align-items:center;gap:11px;width:100%;min-height:42px;padding:9px 11px;border-radius:8px;color:#24302A;text-decoration:none;font-size:13px;font-weight:500;transition:background .15s ease,color .15s ease,transform .15s ease;}
.panel-menu-item svg{width:18px;height:18px;flex:0 0 18px;color:#5C6962;}
.panel-menu-item:hover{background:#F1F7F3;color:#075B2A;transform:translateX(2px);}
.panel-menu-item:hover svg,.panel-menu-item.active svg{color:#0B7A3E;}
.panel-menu-item.active{background:#EAF5EE;color:#075B2A;font-weight:700;}
.panel-menu-divider{height:1px;background:#E8EDE9;margin:7px 5px;}
.panel-badge{margin-left:auto;background:#16834A;color:#fff;min-width:20px;padding:2px 6px;text-align:center;border-radius:20px;font-size:10px;font-weight:700;}
.panel-user-area .user-link{padding:5px 6px;border-radius:10px;transition:background .18s ease;}
.panel-user-area .user-link:hover{background:#F4F8F5;}
/* The old main-nav rules remain for compatibility, but the module links are now in Panel. */
.panel-user-area + *{min-width:0;}
.panel-user-area .user-link{margin-left:2px;}
@media (max-width:900px){
  .panel-user-area{border-left:0;padding-left:0;}
  .panel-menu{right:-8px;width:min(270px,calc(100vw - 24px));}
  .user-copy{display:none;}
}

  /* ===== Polls & Surveys refresh ===== */
  .polls-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:22px;padding:28px 30px;margin-bottom:16px;border:1px solid #DDE6E1;border-radius:18px;background:linear-gradient(135deg,#F7FBF8 0%,#FFFFFF 58%,#FFF9EA 100%);box-shadow:0 12px 30px rgba(20,33,61,.06);}
  .polls-hero:before,.polls-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none;}
  .polls-hero:before{width:180px;height:180px;right:45px;top:-100px;background:rgba(31,139,76,.08);}
  .polls-hero:after{width:120px;height:120px;right:-30px;bottom:-75px;background:rgba(239,178,61,.12);}
  .polls-kicker{font-size:10px;font-weight:800;letter-spacing:1.7px;color:#1F8B4C;margin-bottom:7px;}
  .polls-hero h2{margin:0;font-size:28px;letter-spacing:-.5px;color:var(--ink);}
  .polls-hero .sub{max-width:650px;margin:7px 0 0;line-height:1.6;}
  .polls-hero-icon{position:relative;z-index:1;display:grid;place-items:center;width:68px;height:68px;border-radius:20px;background:#fff;border:1px solid #E5EAE6;box-shadow:0 10px 25px rgba(20,33,61,.08);font-size:30px;color:#EFB23D;animation:pollFloat 3.5s ease-in-out infinite;}
  @keyframes pollFloat{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-5px) rotate(4deg)}}
  .poll-role-note{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:11px 14px;border-radius:12px;background:#fff;border:1px solid var(--line);font-size:12.5px;color:var(--slate);}
  .role-chip{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;}
  .student-chip{background:#EAF7EF;color:#1F8B4C}.officer-chip{background:#FFF3D8;color:#A46B00}.admin-chip{background:#EEEAFE;color:#6652B5}
  .poll-create-btn{box-shadow:0 8px 18px rgba(31,139,76,.16);transition:transform .2s ease,box-shadow .2s ease}.poll-create-btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(31,139,76,.22)}
  .poll-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
  .poll-card{position:relative;overflow:hidden;min-height:210px;padding:21px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(145deg,#fff 0%,#FBFCFB 100%);box-shadow:0 8px 22px rgba(20,33,61,.055);transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease;}
  .poll-card:hover{transform:translateY(-5px);border-color:#C9D9CF;box-shadow:0 16px 32px rgba(20,33,61,.11)}
  .poll-card-glow{position:absolute;width:130px;height:130px;right:-70px;top:-70px;border-radius:50%;background:rgba(31,139,76,.06);transition:transform .35s ease,background .35s ease}.poll-card:hover .poll-card-glow{transform:scale(1.35);background:rgba(239,178,61,.10)}
  .poll-card-top{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;margin-bottom:17px}.poll-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.3px}.poll-badge span{width:6px;height:6px;border-radius:50%;background:currentColor}.poll-badge.is-open{background:#EAF7EF;color:#1F8B4C}.poll-badge.is-closed{background:#F1F2F4;color:#6E7681}.poll-arrow{font-size:20px;color:#9AA3AD;transition:transform .22s ease,color .22s ease}.poll-card:hover .poll-arrow{transform:translateX(4px);color:#1F8B4C}
  .poll-card h3{position:relative;z-index:1;margin:0;font-size:18px;line-height:1.35;color:var(--ink);letter-spacing:-.2px}.poll-card-desc{position:relative;z-index:1;margin:9px 0 0;font-size:12.5px;line-height:1.55;color:var(--slate);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.poll-card-meta{position:relative;z-index:1;display:flex;gap:7px;flex-wrap:wrap;margin-top:15px;font-size:11px;color:#7C8590}.poll-card-footer{position:relative;z-index:1;margin-top:18px;padding-top:13px;border-top:1px solid #EEF1EF}.poll-status-text{font-size:11.5px;font-weight:800}.voted-text{color:#1F8B4C}.vote-now-text{color:#A46B00}.manage-text{color:#6652B5}
  .poll-empty{padding:55px 20px;text-align:center;border:1px dashed #CAD5CE;border-radius:16px;background:#FBFCFB}.poll-empty-icon{font-size:32px;color:#C3CEC6;margin-bottom:8px}.poll-empty .big{font-weight:800;font-size:17px}.poll-empty p{font-size:12.5px;color:var(--slate);margin:7px 0 0}
  .poll-modal{position:relative;overflow:hidden}.poll-modal-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:#FFF5D9;color:#C78C20;font-size:21px;margin-bottom:10px}.poll-modal h3{margin-bottom:5px}.poll-modal .field label span{font-weight:500;color:var(--slate)}
  .poll-detail-card{position:relative;overflow:hidden}.poll-detail-card:before{content:"";position:absolute;right:-70px;top:-80px;width:190px;height:190px;border-radius:50%;background:rgba(31,139,76,.055);pointer-events:none}.poll-detail-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.poll-detail-kicker{font-size:10px;letter-spacing:1.5px;font-weight:800;color:#1F8B4C;margin-bottom:5px}.poll-detail-card .detail-desc{position:relative;padding:12px 14px;border-radius:11px;background:#F8FAF9;border-left:3px solid #EFB23D}.poll-option{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:12px;padding:14px 15px;margin-bottom:11px;background:#fff;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.poll-option:hover{transform:translateX(3px);border-color:#C7D7CC;box-shadow:0 7px 18px rgba(20,33,61,.06)}.poll-option.voted{border-color:#E4B64D;background:linear-gradient(90deg,#FFF9EA,#fff)}.poll-bar{height:9px;background:#EEF2EF;border-radius:99px;overflow:hidden}.poll-bar-fill{height:100%;background:linear-gradient(90deg,#1F8B4C,#48A96D,#EFB23D);background-size:200% 100%;border-radius:99px;animation:pollBarShimmer 2.8s linear infinite}.poll-option.voted .poll-bar-fill{box-shadow:0 0 10px rgba(239,178,61,.28)}@keyframes pollBarShimmer{0%{background-position:0% 0}100%{background-position:200% 0}}
  .vote-form{margin:0 0 10px}.vote-btn{display:flex;align-items:center;gap:12px;width:100%;text-align:left;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px 16px;margin:0;font-size:14px;font-weight:700;color:var(--ink);cursor:pointer;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease}.vote-btn:hover{transform:translateY(-2px);border-color:#9FC0AA;background:#F8FCF9;box-shadow:0 9px 20px rgba(20,33,61,.08)}.vote-btn:active{transform:translateY(0)}.vote-radio{width:17px;height:17px;border:2px solid #B9C5BD;border-radius:50%;flex:0 0 auto;transition:border-color .2s ease,box-shadow .2s ease}.vote-btn:hover .vote-radio{border-color:#1F8B4C;box-shadow:0 0 0 4px rgba(31,139,76,.09)}.vote-chevron{margin-left:auto;color:#A4ADA6;transition:transform .2s ease,color .2s ease}.vote-btn:hover .vote-chevron{transform:translateX(4px);color:#1F8B4C}.poll-hint{margin:14px 0 0;padding:10px 12px;border-radius:10px;background:#F7F9F8;color:var(--slate);font-size:11.5px}
  @media (max-width:760px){.poll-grid{grid-template-columns:1fr}.polls-hero{padding:22px}.polls-hero-icon{width:55px;height:55px;font-size:24px}.poll-detail-head{flex-direction:column}.poll-detail-head>.badge{align-self:flex-start}}
  @media (prefers-reduced-motion:reduce){.polls-hero-icon,.poll-bar-fill{animation:none}.poll-card,.vote-btn,.poll-option{transition:none}}


  /* ===== NBSC Green Archers theme for Polls & Surveys ===== */
  .polls-hero{
    border:1px solid rgba(6,92,45,.18);
    background:
      radial-gradient(circle at 86% 18%, rgba(215,169,40,.20) 0 2px, transparent 3px),
      radial-gradient(circle at 76% 72%, rgba(255,255,255,.55) 0 3px, transparent 4px),
      linear-gradient(135deg,#EAF7EE 0%,#F8FCF9 52%,#E7F2EA 100%);
    box-shadow:0 16px 38px rgba(6,76,37,.10), inset 0 1px 0 rgba(255,255,255,.85);
  }
  .polls-hero:before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    right:-90px;top:-115px;
    border:2px solid rgba(6,92,45,.12);
    border-radius:50%;
    box-shadow:
      0 0 0 22px rgba(6,92,45,.045),
      0 0 0 44px rgba(215,169,40,.055),
      0 0 0 66px rgba(6,92,45,.035);
    transform:rotate(-18deg);
    pointer-events:none;
  }
  .polls-hero:after{
    content:'';
    position:absolute;
    left:-35px;bottom:-72px;
    width:210px;height:140px;
    background:
      repeating-linear-gradient(135deg,
        rgba(6,92,45,.08) 0 2px,
        transparent 2px 15px);
    transform:skewX(-18deg);
    opacity:.7;
    pointer-events:none;
  }
  .polls-hero > *{position:relative;z-index:2;}
  .polls-hero .eyebrow,
  .polls-hero .hero-eyebrow{
    color:#0B7A3B !important;
    letter-spacing:1.2px;
    font-weight:800;
  }
  .polls-hero h1,.polls-hero h2,.polls-hero h3{
    color:#064C25;
  }
  .polls-hero p{color:#4E6657;}
  .polls-hero .btn-primary,
  .polls-hero .btn-green{
    background:linear-gradient(135deg,#075B2A,#0B7A3B);
    border-color:#075B2A;
    color:#fff;
    box-shadow:0 7px 18px rgba(7,91,42,.22);
  }
  .polls-hero .btn-primary:hover,
  .polls-hero .btn-green:hover{
    background:linear-gradient(135deg,#064C25,#096B34);
    transform:translateY(-1px);
  }
  .poll-option{
    border-color:rgba(7,91,42,.14);
    background:rgba(255,255,255,.78);
    box-shadow:0 5px 16px rgba(6,76,37,.055);
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }
  .poll-option:hover{
    transform:translateY(-2px);
    border-color:rgba(7,91,42,.32);
    box-shadow:0 9px 22px rgba(6,76,37,.10);
  }
  .poll-option.voted{
    border-color:#0B7A3B;
    background:#EDF8F0;
    box-shadow:0 7px 18px rgba(7,91,42,.10);
  }
  .poll-bar{background:#DCEBE0;}
  .poll-bar-fill{
    background:linear-gradient(90deg,#064C25,#0B7A3B 72%,#D7A928);
    box-shadow:0 1px 5px rgba(7,91,42,.18);
  }
  .vote-btn{
    border-color:rgba(7,91,42,.15);
    background:rgba(255,255,255,.84);
  }
  .vote-btn:hover{
    border-color:#0B7A3B;
    background:#EDF8F0;
    color:#064C25;
  }
  .poll-stat,.poll-card,.survey-card{
    border-color:rgba(7,91,42,.14);
    box-shadow:0 9px 24px rgba(6,76,37,.07);
  }
  .poll-status-active,.survey-status-active{
    background:#E7F6EB !important;
    color:#075B2A !important;
    border-color:#BFE3C8 !important;
  }
  .polls-page .card,
  .polls-page .panel{
    border-color:rgba(7,91,42,.13);
  }
  @media (prefers-reduced-motion:no-preference){
    .polls-hero:before{
      animation:nbsc-archer-glow 8s ease-in-out infinite alternate;
    }
    @keyframes nbsc-archer-glow{
      from{transform:rotate(-18deg) scale(1);}
      to{transform:rotate(-12deg) scale(1.04);}
    }
  }


/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* Final header arrangement: keep Panel/Profile on the left side, with clear separation from branding. */
.sidebar{justify-content:flex-start;gap:0;}
.sidebar .brand{flex:0 0 250px;width:250px;}
.sidebar .panel-user-area{
  margin-left:90px;
  gap:18px;
  flex:0 0 auto;
  align-items:center;
}
.sidebar .panel-dropdown-wrap{flex:0 0 auto;}
.sidebar .panel-trigger{min-width:78px;justify-content:center;padding:0 12px;}
.sidebar .panel-user-area .user-link{margin-left:4px;padding:5px 9px;}
.sidebar .panel-user-area .user-copy{display:flex;}
@media (max-width:1180px){
  .sidebar{padding-left:26px;padding-right:26px;}
  .sidebar .brand{flex-basis:230px;width:230px;}
  .sidebar .panel-user-area{margin-left:55px;gap:14px;}
}
@media (max-width:900px){
  .sidebar{padding:10px 16px;gap:0;}
  .sidebar .brand{flex:1 1 auto;width:auto;}
  .sidebar .panel-user-area{margin-left:28px;gap:8px;}
  .sidebar .panel-user-area .user-copy{display:none;}
}
@media (max-width:620px){
  .sidebar .panel-user-area{margin-left:12px;}
  .sidebar .panel-trigger{min-width:64px;font-size:12px;}
}

  /* ===== Polls & Surveys refresh ===== */
  .polls-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:22px;padding:28px 30px;margin-bottom:16px;border:1px solid #DDE6E1;border-radius:18px;background:linear-gradient(135deg,#F7FBF8 0%,#FFFFFF 58%,#FFF9EA 100%);box-shadow:0 12px 30px rgba(20,33,61,.06);}
  .polls-hero:before,.polls-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none;}
  .polls-hero:before{width:180px;height:180px;right:45px;top:-100px;background:rgba(31,139,76,.08);}
  .polls-hero:after{width:120px;height:120px;right:-30px;bottom:-75px;background:rgba(239,178,61,.12);}
  .polls-kicker{font-size:10px;font-weight:800;letter-spacing:1.7px;color:#1F8B4C;margin-bottom:7px;}
  .polls-hero h2{margin:0;font-size:28px;letter-spacing:-.5px;color:var(--ink);}
  .polls-hero .sub{max-width:650px;margin:7px 0 0;line-height:1.6;}
  .polls-hero-icon{position:relative;z-index:1;display:grid;place-items:center;width:68px;height:68px;border-radius:20px;background:#fff;border:1px solid #E5EAE6;box-shadow:0 10px 25px rgba(20,33,61,.08);font-size:30px;color:#EFB23D;animation:pollFloat 3.5s ease-in-out infinite;}
  @keyframes pollFloat{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-5px) rotate(4deg)}}
  .poll-role-note{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:11px 14px;border-radius:12px;background:#fff;border:1px solid var(--line);font-size:12.5px;color:var(--slate);}
  .role-chip{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;}
  .student-chip{background:#EAF7EF;color:#1F8B4C}.officer-chip{background:#FFF3D8;color:#A46B00}.admin-chip{background:#EEEAFE;color:#6652B5}
  .poll-create-btn{box-shadow:0 8px 18px rgba(31,139,76,.16);transition:transform .2s ease,box-shadow .2s ease}.poll-create-btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(31,139,76,.22)}
  .poll-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
  .poll-card{position:relative;overflow:hidden;min-height:210px;padding:21px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(145deg,#fff 0%,#FBFCFB 100%);box-shadow:0 8px 22px rgba(20,33,61,.055);transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease;}
  .poll-card:hover{transform:translateY(-5px);border-color:#C9D9CF;box-shadow:0 16px 32px rgba(20,33,61,.11)}
  .poll-card-glow{position:absolute;width:130px;height:130px;right:-70px;top:-70px;border-radius:50%;background:rgba(31,139,76,.06);transition:transform .35s ease,background .35s ease}.poll-card:hover .poll-card-glow{transform:scale(1.35);background:rgba(239,178,61,.10)}
  .poll-card-top{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;margin-bottom:17px}.poll-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.3px}.poll-badge span{width:6px;height:6px;border-radius:50%;background:currentColor}.poll-badge.is-open{background:#EAF7EF;color:#1F8B4C}.poll-badge.is-closed{background:#F1F2F4;color:#6E7681}.poll-arrow{font-size:20px;color:#9AA3AD;transition:transform .22s ease,color .22s ease}.poll-card:hover .poll-arrow{transform:translateX(4px);color:#1F8B4C}
  .poll-card h3{position:relative;z-index:1;margin:0;font-size:18px;line-height:1.35;color:var(--ink);letter-spacing:-.2px}.poll-card-desc{position:relative;z-index:1;margin:9px 0 0;font-size:12.5px;line-height:1.55;color:var(--slate);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.poll-card-meta{position:relative;z-index:1;display:flex;gap:7px;flex-wrap:wrap;margin-top:15px;font-size:11px;color:#7C8590}.poll-card-footer{position:relative;z-index:1;margin-top:18px;padding-top:13px;border-top:1px solid #EEF1EF}.poll-status-text{font-size:11.5px;font-weight:800}.voted-text{color:#1F8B4C}.vote-now-text{color:#A46B00}.manage-text{color:#6652B5}
  .poll-empty{padding:55px 20px;text-align:center;border:1px dashed #CAD5CE;border-radius:16px;background:#FBFCFB}.poll-empty-icon{font-size:32px;color:#C3CEC6;margin-bottom:8px}.poll-empty .big{font-weight:800;font-size:17px}.poll-empty p{font-size:12.5px;color:var(--slate);margin:7px 0 0}
  .poll-modal{position:relative;overflow:hidden}.poll-modal-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:#FFF5D9;color:#C78C20;font-size:21px;margin-bottom:10px}.poll-modal h3{margin-bottom:5px}.poll-modal .field label span{font-weight:500;color:var(--slate)}
  .poll-detail-card{position:relative;overflow:hidden}.poll-detail-card:before{content:"";position:absolute;right:-70px;top:-80px;width:190px;height:190px;border-radius:50%;background:rgba(31,139,76,.055);pointer-events:none}.poll-detail-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.poll-detail-kicker{font-size:10px;letter-spacing:1.5px;font-weight:800;color:#1F8B4C;margin-bottom:5px}.poll-detail-card .detail-desc{position:relative;padding:12px 14px;border-radius:11px;background:#F8FAF9;border-left:3px solid #EFB23D}.poll-option{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:12px;padding:14px 15px;margin-bottom:11px;background:#fff;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.poll-option:hover{transform:translateX(3px);border-color:#C7D7CC;box-shadow:0 7px 18px rgba(20,33,61,.06)}.poll-option.voted{border-color:#E4B64D;background:linear-gradient(90deg,#FFF9EA,#fff)}.poll-bar{height:9px;background:#EEF2EF;border-radius:99px;overflow:hidden}.poll-bar-fill{height:100%;background:linear-gradient(90deg,#1F8B4C,#48A96D,#EFB23D);background-size:200% 100%;border-radius:99px;animation:pollBarShimmer 2.8s linear infinite}.poll-option.voted .poll-bar-fill{box-shadow:0 0 10px rgba(239,178,61,.28)}@keyframes pollBarShimmer{0%{background-position:0% 0}100%{background-position:200% 0}}
  .vote-form{margin:0 0 10px}.vote-btn{display:flex;align-items:center;gap:12px;width:100%;text-align:left;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px 16px;margin:0;font-size:14px;font-weight:700;color:var(--ink);cursor:pointer;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease}.vote-btn:hover{transform:translateY(-2px);border-color:#9FC0AA;background:#F8FCF9;box-shadow:0 9px 20px rgba(20,33,61,.08)}.vote-btn:active{transform:translateY(0)}.vote-radio{width:17px;height:17px;border:2px solid #B9C5BD;border-radius:50%;flex:0 0 auto;transition:border-color .2s ease,box-shadow .2s ease}.vote-btn:hover .vote-radio{border-color:#1F8B4C;box-shadow:0 0 0 4px rgba(31,139,76,.09)}.vote-chevron{margin-left:auto;color:#A4ADA6;transition:transform .2s ease,color .2s ease}.vote-btn:hover .vote-chevron{transform:translateX(4px);color:#1F8B4C}.poll-hint{margin:14px 0 0;padding:10px 12px;border-radius:10px;background:#F7F9F8;color:var(--slate);font-size:11.5px}
  @media (max-width:760px){.poll-grid{grid-template-columns:1fr}.polls-hero{padding:22px}.polls-hero-icon{width:55px;height:55px;font-size:24px}.poll-detail-head{flex-direction:column}.poll-detail-head>.badge{align-self:flex-start}}
  @media (prefers-reduced-motion:reduce){.polls-hero-icon,.poll-bar-fill{animation:none}.poll-card,.vote-btn,.poll-option{transition:none}}


  /* ===== NBSC Green Archers theme for Polls & Surveys ===== */
  .polls-hero{
    border:1px solid rgba(6,92,45,.18);
    background:
      radial-gradient(circle at 86% 18%, rgba(215,169,40,.20) 0 2px, transparent 3px),
      radial-gradient(circle at 76% 72%, rgba(255,255,255,.55) 0 3px, transparent 4px),
      linear-gradient(135deg,#EAF7EE 0%,#F8FCF9 52%,#E7F2EA 100%);
    box-shadow:0 16px 38px rgba(6,76,37,.10), inset 0 1px 0 rgba(255,255,255,.85);
  }
  .polls-hero:before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    right:-90px;top:-115px;
    border:2px solid rgba(6,92,45,.12);
    border-radius:50%;
    box-shadow:
      0 0 0 22px rgba(6,92,45,.045),
      0 0 0 44px rgba(215,169,40,.055),
      0 0 0 66px rgba(6,92,45,.035);
    transform:rotate(-18deg);
    pointer-events:none;
  }
  .polls-hero:after{
    content:'';
    position:absolute;
    left:-35px;bottom:-72px;
    width:210px;height:140px;
    background:
      repeating-linear-gradient(135deg,
        rgba(6,92,45,.08) 0 2px,
        transparent 2px 15px);
    transform:skewX(-18deg);
    opacity:.7;
    pointer-events:none;
  }
  .polls-hero > *{position:relative;z-index:2;}
  .polls-hero .eyebrow,
  .polls-hero .hero-eyebrow{
    color:#0B7A3B !important;
    letter-spacing:1.2px;
    font-weight:800;
  }
  .polls-hero h1,.polls-hero h2,.polls-hero h3{
    color:#064C25;
  }
  .polls-hero p{color:#4E6657;}
  .polls-hero .btn-primary,
  .polls-hero .btn-green{
    background:linear-gradient(135deg,#075B2A,#0B7A3B);
    border-color:#075B2A;
    color:#fff;
    box-shadow:0 7px 18px rgba(7,91,42,.22);
  }
  .polls-hero .btn-primary:hover,
  .polls-hero .btn-green:hover{
    background:linear-gradient(135deg,#064C25,#096B34);
    transform:translateY(-1px);
  }
  .poll-option{
    border-color:rgba(7,91,42,.14);
    background:rgba(255,255,255,.78);
    box-shadow:0 5px 16px rgba(6,76,37,.055);
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }
  .poll-option:hover{
    transform:translateY(-2px);
    border-color:rgba(7,91,42,.32);
    box-shadow:0 9px 22px rgba(6,76,37,.10);
  }
  .poll-option.voted{
    border-color:#0B7A3B;
    background:#EDF8F0;
    box-shadow:0 7px 18px rgba(7,91,42,.10);
  }
  .poll-bar{background:#DCEBE0;}
  .poll-bar-fill{
    background:linear-gradient(90deg,#064C25,#0B7A3B 72%,#D7A928);
    box-shadow:0 1px 5px rgba(7,91,42,.18);
  }
  .vote-btn{
    border-color:rgba(7,91,42,.15);
    background:rgba(255,255,255,.84);
  }
  .vote-btn:hover{
    border-color:#0B7A3B;
    background:#EDF8F0;
    color:#064C25;
  }
  .poll-stat,.poll-card,.survey-card{
    border-color:rgba(7,91,42,.14);
    box-shadow:0 9px 24px rgba(6,76,37,.07);
  }
  .poll-status-active,.survey-status-active{
    background:#E7F6EB !important;
    color:#075B2A !important;
    border-color:#BFE3C8 !important;
  }
  .polls-page .card,
  .polls-page .panel{
    border-color:rgba(7,91,42,.13);
  }
  @media (prefers-reduced-motion:no-preference){
    .polls-hero:before{
      animation:nbsc-archer-glow 8s ease-in-out infinite alternate;
    }
    @keyframes nbsc-archer-glow{
      from{transform:rotate(-18deg) scale(1);}
      to{transform:rotate(-12deg) scale(1.04);}
    }
  }


/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* Header placement: Panel + Profile sit in the far end of the LEFT section,
   with generous separation from the SBO Nexus branding and without moving to the right edge. */
.sidebar{justify-content:flex-start;}
.sidebar .brand{flex:0 0 270px;width:270px;}
.sidebar .panel-user-area{
  margin-left:clamp(140px, 22vw, 360px);
  gap:20px;
  flex:0 0 auto;
}
.sidebar .panel-trigger{min-width:82px;justify-content:center;padding:0 13px;}
.sidebar .panel-user-area .user-link{margin-left:6px;padding:6px 10px;}
@media (max-width:1180px){
  .sidebar .brand{flex-basis:245px;width:245px;}
  .sidebar .panel-user-area{margin-left:clamp(90px, 15vw, 220px);gap:14px;}
}
@media (max-width:900px){
  .sidebar .brand{flex:1 1 auto;width:auto;}
  .sidebar .panel-user-area{margin-left:35px;gap:9px;}
  .sidebar .panel-user-area .user-copy{display:none;}
}
@media (max-width:620px){
  .sidebar .panel-user-area{margin-left:18px;}
  .sidebar .panel-trigger{min-width:64px;font-size:12px;}
}

  /* ===== Polls & Surveys refresh ===== */
  .polls-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:22px;padding:28px 30px;margin-bottom:16px;border:1px solid #DDE6E1;border-radius:18px;background:linear-gradient(135deg,#F7FBF8 0%,#FFFFFF 58%,#FFF9EA 100%);box-shadow:0 12px 30px rgba(20,33,61,.06);}
  .polls-hero:before,.polls-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none;}
  .polls-hero:before{width:180px;height:180px;right:45px;top:-100px;background:rgba(31,139,76,.08);}
  .polls-hero:after{width:120px;height:120px;right:-30px;bottom:-75px;background:rgba(239,178,61,.12);}
  .polls-kicker{font-size:10px;font-weight:800;letter-spacing:1.7px;color:#1F8B4C;margin-bottom:7px;}
  .polls-hero h2{margin:0;font-size:28px;letter-spacing:-.5px;color:var(--ink);}
  .polls-hero .sub{max-width:650px;margin:7px 0 0;line-height:1.6;}
  .polls-hero-icon{position:relative;z-index:1;display:grid;place-items:center;width:68px;height:68px;border-radius:20px;background:#fff;border:1px solid #E5EAE6;box-shadow:0 10px 25px rgba(20,33,61,.08);font-size:30px;color:#EFB23D;animation:pollFloat 3.5s ease-in-out infinite;}
  @keyframes pollFloat{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-5px) rotate(4deg)}}
  .poll-role-note{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:11px 14px;border-radius:12px;background:#fff;border:1px solid var(--line);font-size:12.5px;color:var(--slate);}
  .role-chip{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;}
  .student-chip{background:#EAF7EF;color:#1F8B4C}.officer-chip{background:#FFF3D8;color:#A46B00}.admin-chip{background:#EEEAFE;color:#6652B5}
  .poll-create-btn{box-shadow:0 8px 18px rgba(31,139,76,.16);transition:transform .2s ease,box-shadow .2s ease}.poll-create-btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(31,139,76,.22)}
  .poll-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
  .poll-card{position:relative;overflow:hidden;min-height:210px;padding:21px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(145deg,#fff 0%,#FBFCFB 100%);box-shadow:0 8px 22px rgba(20,33,61,.055);transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease;}
  .poll-card:hover{transform:translateY(-5px);border-color:#C9D9CF;box-shadow:0 16px 32px rgba(20,33,61,.11)}
  .poll-card-glow{position:absolute;width:130px;height:130px;right:-70px;top:-70px;border-radius:50%;background:rgba(31,139,76,.06);transition:transform .35s ease,background .35s ease}.poll-card:hover .poll-card-glow{transform:scale(1.35);background:rgba(239,178,61,.10)}
  .poll-card-top{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;margin-bottom:17px}.poll-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.3px}.poll-badge span{width:6px;height:6px;border-radius:50%;background:currentColor}.poll-badge.is-open{background:#EAF7EF;color:#1F8B4C}.poll-badge.is-closed{background:#F1F2F4;color:#6E7681}.poll-arrow{font-size:20px;color:#9AA3AD;transition:transform .22s ease,color .22s ease}.poll-card:hover .poll-arrow{transform:translateX(4px);color:#1F8B4C}
  .poll-card h3{position:relative;z-index:1;margin:0;font-size:18px;line-height:1.35;color:var(--ink);letter-spacing:-.2px}.poll-card-desc{position:relative;z-index:1;margin:9px 0 0;font-size:12.5px;line-height:1.55;color:var(--slate);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.poll-card-meta{position:relative;z-index:1;display:flex;gap:7px;flex-wrap:wrap;margin-top:15px;font-size:11px;color:#7C8590}.poll-card-footer{position:relative;z-index:1;margin-top:18px;padding-top:13px;border-top:1px solid #EEF1EF}.poll-status-text{font-size:11.5px;font-weight:800}.voted-text{color:#1F8B4C}.vote-now-text{color:#A46B00}.manage-text{color:#6652B5}
  .poll-empty{padding:55px 20px;text-align:center;border:1px dashed #CAD5CE;border-radius:16px;background:#FBFCFB}.poll-empty-icon{font-size:32px;color:#C3CEC6;margin-bottom:8px}.poll-empty .big{font-weight:800;font-size:17px}.poll-empty p{font-size:12.5px;color:var(--slate);margin:7px 0 0}
  .poll-modal{position:relative;overflow:hidden}.poll-modal-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:#FFF5D9;color:#C78C20;font-size:21px;margin-bottom:10px}.poll-modal h3{margin-bottom:5px}.poll-modal .field label span{font-weight:500;color:var(--slate)}
  .poll-detail-card{position:relative;overflow:hidden}.poll-detail-card:before{content:"";position:absolute;right:-70px;top:-80px;width:190px;height:190px;border-radius:50%;background:rgba(31,139,76,.055);pointer-events:none}.poll-detail-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.poll-detail-kicker{font-size:10px;letter-spacing:1.5px;font-weight:800;color:#1F8B4C;margin-bottom:5px}.poll-detail-card .detail-desc{position:relative;padding:12px 14px;border-radius:11px;background:#F8FAF9;border-left:3px solid #EFB23D}.poll-option{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:12px;padding:14px 15px;margin-bottom:11px;background:#fff;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.poll-option:hover{transform:translateX(3px);border-color:#C7D7CC;box-shadow:0 7px 18px rgba(20,33,61,.06)}.poll-option.voted{border-color:#E4B64D;background:linear-gradient(90deg,#FFF9EA,#fff)}.poll-bar{height:9px;background:#EEF2EF;border-radius:99px;overflow:hidden}.poll-bar-fill{height:100%;background:linear-gradient(90deg,#1F8B4C,#48A96D,#EFB23D);background-size:200% 100%;border-radius:99px;animation:pollBarShimmer 2.8s linear infinite}.poll-option.voted .poll-bar-fill{box-shadow:0 0 10px rgba(239,178,61,.28)}@keyframes pollBarShimmer{0%{background-position:0% 0}100%{background-position:200% 0}}
  .vote-form{margin:0 0 10px}.vote-btn{display:flex;align-items:center;gap:12px;width:100%;text-align:left;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px 16px;margin:0;font-size:14px;font-weight:700;color:var(--ink);cursor:pointer;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease}.vote-btn:hover{transform:translateY(-2px);border-color:#9FC0AA;background:#F8FCF9;box-shadow:0 9px 20px rgba(20,33,61,.08)}.vote-btn:active{transform:translateY(0)}.vote-radio{width:17px;height:17px;border:2px solid #B9C5BD;border-radius:50%;flex:0 0 auto;transition:border-color .2s ease,box-shadow .2s ease}.vote-btn:hover .vote-radio{border-color:#1F8B4C;box-shadow:0 0 0 4px rgba(31,139,76,.09)}.vote-chevron{margin-left:auto;color:#A4ADA6;transition:transform .2s ease,color .2s ease}.vote-btn:hover .vote-chevron{transform:translateX(4px);color:#1F8B4C}.poll-hint{margin:14px 0 0;padding:10px 12px;border-radius:10px;background:#F7F9F8;color:var(--slate);font-size:11.5px}
  @media (max-width:760px){.poll-grid{grid-template-columns:1fr}.polls-hero{padding:22px}.polls-hero-icon{width:55px;height:55px;font-size:24px}.poll-detail-head{flex-direction:column}.poll-detail-head>.badge{align-self:flex-start}}
  @media (prefers-reduced-motion:reduce){.polls-hero-icon,.poll-bar-fill{animation:none}.poll-card,.vote-btn,.poll-option{transition:none}}


  /* ===== NBSC Green Archers theme for Polls & Surveys ===== */
  .polls-hero{
    border:1px solid rgba(6,92,45,.18);
    background:
      radial-gradient(circle at 86% 18%, rgba(215,169,40,.20) 0 2px, transparent 3px),
      radial-gradient(circle at 76% 72%, rgba(255,255,255,.55) 0 3px, transparent 4px),
      linear-gradient(135deg,#EAF7EE 0%,#F8FCF9 52%,#E7F2EA 100%);
    box-shadow:0 16px 38px rgba(6,76,37,.10), inset 0 1px 0 rgba(255,255,255,.85);
  }
  .polls-hero:before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    right:-90px;top:-115px;
    border:2px solid rgba(6,92,45,.12);
    border-radius:50%;
    box-shadow:
      0 0 0 22px rgba(6,92,45,.045),
      0 0 0 44px rgba(215,169,40,.055),
      0 0 0 66px rgba(6,92,45,.035);
    transform:rotate(-18deg);
    pointer-events:none;
  }
  .polls-hero:after{
    content:'';
    position:absolute;
    left:-35px;bottom:-72px;
    width:210px;height:140px;
    background:
      repeating-linear-gradient(135deg,
        rgba(6,92,45,.08) 0 2px,
        transparent 2px 15px);
    transform:skewX(-18deg);
    opacity:.7;
    pointer-events:none;
  }
  .polls-hero > *{position:relative;z-index:2;}
  .polls-hero .eyebrow,
  .polls-hero .hero-eyebrow{
    color:#0B7A3B !important;
    letter-spacing:1.2px;
    font-weight:800;
  }
  .polls-hero h1,.polls-hero h2,.polls-hero h3{
    color:#064C25;
  }
  .polls-hero p{color:#4E6657;}
  .polls-hero .btn-primary,
  .polls-hero .btn-green{
    background:linear-gradient(135deg,#075B2A,#0B7A3B);
    border-color:#075B2A;
    color:#fff;
    box-shadow:0 7px 18px rgba(7,91,42,.22);
  }
  .polls-hero .btn-primary:hover,
  .polls-hero .btn-green:hover{
    background:linear-gradient(135deg,#064C25,#096B34);
    transform:translateY(-1px);
  }
  .poll-option{
    border-color:rgba(7,91,42,.14);
    background:rgba(255,255,255,.78);
    box-shadow:0 5px 16px rgba(6,76,37,.055);
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }
  .poll-option:hover{
    transform:translateY(-2px);
    border-color:rgba(7,91,42,.32);
    box-shadow:0 9px 22px rgba(6,76,37,.10);
  }
  .poll-option.voted{
    border-color:#0B7A3B;
    background:#EDF8F0;
    box-shadow:0 7px 18px rgba(7,91,42,.10);
  }
  .poll-bar{background:#DCEBE0;}
  .poll-bar-fill{
    background:linear-gradient(90deg,#064C25,#0B7A3B 72%,#D7A928);
    box-shadow:0 1px 5px rgba(7,91,42,.18);
  }
  .vote-btn{
    border-color:rgba(7,91,42,.15);
    background:rgba(255,255,255,.84);
  }
  .vote-btn:hover{
    border-color:#0B7A3B;
    background:#EDF8F0;
    color:#064C25;
  }
  .poll-stat,.poll-card,.survey-card{
    border-color:rgba(7,91,42,.14);
    box-shadow:0 9px 24px rgba(6,76,37,.07);
  }
  .poll-status-active,.survey-status-active{
    background:#E7F6EB !important;
    color:#075B2A !important;
    border-color:#BFE3C8 !important;
  }
  .polls-page .card,
  .polls-page .panel{
    border-color:rgba(7,91,42,.13);
  }
  @media (prefers-reduced-motion:no-preference){
    .polls-hero:before{
      animation:nbsc-archer-glow 8s ease-in-out infinite alternate;
    }
    @keyframes nbsc-archer-glow{
      from{transform:rotate(-18deg) scale(1);}
      to{transform:rotate(-12deg) scale(1.04);}
    }
  }


/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>

<style>
/* Final header position: keep branding on the left and move Panel/Profile to the far end of the header. */
.sidebar{justify-content:flex-start;gap:0;}
.sidebar .brand{flex:0 0 270px;width:270px;}
.sidebar .panel-user-area{margin-left:auto;gap:20px;flex:0 0 auto;}
.sidebar .panel-user-area .user-link{margin-left:0;padding:6px 10px;}
@media (max-width:1180px){
  .sidebar{padding-left:26px;padding-right:26px;}
  .sidebar .brand{flex-basis:245px;width:245px;}
  .sidebar .panel-user-area{gap:14px;}
}
@media (max-width:900px){
  .sidebar{padding:10px 16px;}
  .sidebar .brand{flex:1 1 auto;width:auto;min-width:0;}
  .sidebar .panel-user-area{margin-left:auto;gap:9px;}
  .sidebar .panel-user-area .user-copy{display:none;}
}
@media (max-width:620px){
  .sidebar .panel-user-area{gap:5px;}
  .sidebar .panel-trigger{min-width:64px;font-size:12px;}
}

  /* ===== Polls & Surveys refresh ===== */
  .polls-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:22px;padding:28px 30px;margin-bottom:16px;border:1px solid #DDE6E1;border-radius:18px;background:linear-gradient(135deg,#F7FBF8 0%,#FFFFFF 58%,#FFF9EA 100%);box-shadow:0 12px 30px rgba(20,33,61,.06);}
  .polls-hero:before,.polls-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none;}
  .polls-hero:before{width:180px;height:180px;right:45px;top:-100px;background:rgba(31,139,76,.08);}
  .polls-hero:after{width:120px;height:120px;right:-30px;bottom:-75px;background:rgba(239,178,61,.12);}
  .polls-kicker{font-size:10px;font-weight:800;letter-spacing:1.7px;color:#1F8B4C;margin-bottom:7px;}
  .polls-hero h2{margin:0;font-size:28px;letter-spacing:-.5px;color:var(--ink);}
  .polls-hero .sub{max-width:650px;margin:7px 0 0;line-height:1.6;}
  .polls-hero-icon{position:relative;z-index:1;display:grid;place-items:center;width:68px;height:68px;border-radius:20px;background:#fff;border:1px solid #E5EAE6;box-shadow:0 10px 25px rgba(20,33,61,.08);font-size:30px;color:#EFB23D;animation:pollFloat 3.5s ease-in-out infinite;}
  @keyframes pollFloat{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-5px) rotate(4deg)}}
  .poll-role-note{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:11px 14px;border-radius:12px;background:#fff;border:1px solid var(--line);font-size:12.5px;color:var(--slate);}
  .role-chip{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;}
  .student-chip{background:#EAF7EF;color:#1F8B4C}.officer-chip{background:#FFF3D8;color:#A46B00}.admin-chip{background:#EEEAFE;color:#6652B5}
  .poll-create-btn{box-shadow:0 8px 18px rgba(31,139,76,.16);transition:transform .2s ease,box-shadow .2s ease}.poll-create-btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(31,139,76,.22)}
  .poll-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
  .poll-card{position:relative;overflow:hidden;min-height:210px;padding:21px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(145deg,#fff 0%,#FBFCFB 100%);box-shadow:0 8px 22px rgba(20,33,61,.055);transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease;}
  .poll-card:hover{transform:translateY(-5px);border-color:#C9D9CF;box-shadow:0 16px 32px rgba(20,33,61,.11)}
  .poll-card-glow{position:absolute;width:130px;height:130px;right:-70px;top:-70px;border-radius:50%;background:rgba(31,139,76,.06);transition:transform .35s ease,background .35s ease}.poll-card:hover .poll-card-glow{transform:scale(1.35);background:rgba(239,178,61,.10)}
  .poll-card-top{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;margin-bottom:17px}.poll-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.3px}.poll-badge span{width:6px;height:6px;border-radius:50%;background:currentColor}.poll-badge.is-open{background:#EAF7EF;color:#1F8B4C}.poll-badge.is-closed{background:#F1F2F4;color:#6E7681}.poll-arrow{font-size:20px;color:#9AA3AD;transition:transform .22s ease,color .22s ease}.poll-card:hover .poll-arrow{transform:translateX(4px);color:#1F8B4C}
  .poll-card h3{position:relative;z-index:1;margin:0;font-size:18px;line-height:1.35;color:var(--ink);letter-spacing:-.2px}.poll-card-desc{position:relative;z-index:1;margin:9px 0 0;font-size:12.5px;line-height:1.55;color:var(--slate);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.poll-card-meta{position:relative;z-index:1;display:flex;gap:7px;flex-wrap:wrap;margin-top:15px;font-size:11px;color:#7C8590}.poll-card-footer{position:relative;z-index:1;margin-top:18px;padding-top:13px;border-top:1px solid #EEF1EF}.poll-status-text{font-size:11.5px;font-weight:800}.voted-text{color:#1F8B4C}.vote-now-text{color:#A46B00}.manage-text{color:#6652B5}
  .poll-empty{padding:55px 20px;text-align:center;border:1px dashed #CAD5CE;border-radius:16px;background:#FBFCFB}.poll-empty-icon{font-size:32px;color:#C3CEC6;margin-bottom:8px}.poll-empty .big{font-weight:800;font-size:17px}.poll-empty p{font-size:12.5px;color:var(--slate);margin:7px 0 0}
  .poll-modal{position:relative;overflow:hidden}.poll-modal-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:#FFF5D9;color:#C78C20;font-size:21px;margin-bottom:10px}.poll-modal h3{margin-bottom:5px}.poll-modal .field label span{font-weight:500;color:var(--slate)}
  .poll-detail-card{position:relative;overflow:hidden}.poll-detail-card:before{content:"";position:absolute;right:-70px;top:-80px;width:190px;height:190px;border-radius:50%;background:rgba(31,139,76,.055);pointer-events:none}.poll-detail-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.poll-detail-kicker{font-size:10px;letter-spacing:1.5px;font-weight:800;color:#1F8B4C;margin-bottom:5px}.poll-detail-card .detail-desc{position:relative;padding:12px 14px;border-radius:11px;background:#F8FAF9;border-left:3px solid #EFB23D}.poll-option{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:12px;padding:14px 15px;margin-bottom:11px;background:#fff;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.poll-option:hover{transform:translateX(3px);border-color:#C7D7CC;box-shadow:0 7px 18px rgba(20,33,61,.06)}.poll-option.voted{border-color:#E4B64D;background:linear-gradient(90deg,#FFF9EA,#fff)}.poll-bar{height:9px;background:#EEF2EF;border-radius:99px;overflow:hidden}.poll-bar-fill{height:100%;background:linear-gradient(90deg,#1F8B4C,#48A96D,#EFB23D);background-size:200% 100%;border-radius:99px;animation:pollBarShimmer 2.8s linear infinite}.poll-option.voted .poll-bar-fill{box-shadow:0 0 10px rgba(239,178,61,.28)}@keyframes pollBarShimmer{0%{background-position:0% 0}100%{background-position:200% 0}}
  .vote-form{margin:0 0 10px}.vote-btn{display:flex;align-items:center;gap:12px;width:100%;text-align:left;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px 16px;margin:0;font-size:14px;font-weight:700;color:var(--ink);cursor:pointer;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease}.vote-btn:hover{transform:translateY(-2px);border-color:#9FC0AA;background:#F8FCF9;box-shadow:0 9px 20px rgba(20,33,61,.08)}.vote-btn:active{transform:translateY(0)}.vote-radio{width:17px;height:17px;border:2px solid #B9C5BD;border-radius:50%;flex:0 0 auto;transition:border-color .2s ease,box-shadow .2s ease}.vote-btn:hover .vote-radio{border-color:#1F8B4C;box-shadow:0 0 0 4px rgba(31,139,76,.09)}.vote-chevron{margin-left:auto;color:#A4ADA6;transition:transform .2s ease,color .2s ease}.vote-btn:hover .vote-chevron{transform:translateX(4px);color:#1F8B4C}.poll-hint{margin:14px 0 0;padding:10px 12px;border-radius:10px;background:#F7F9F8;color:var(--slate);font-size:11.5px}
  @media (max-width:760px){.poll-grid{grid-template-columns:1fr}.polls-hero{padding:22px}.polls-hero-icon{width:55px;height:55px;font-size:24px}.poll-detail-head{flex-direction:column}.poll-detail-head>.badge{align-self:flex-start}}
  @media (prefers-reduced-motion:reduce){.polls-hero-icon,.poll-bar-fill{animation:none}.poll-card,.vote-btn,.poll-option{transition:none}}


  /* ===== NBSC Green Archers theme for Polls & Surveys ===== */
  .polls-hero{
    border:1px solid rgba(6,92,45,.18);
    background:
      radial-gradient(circle at 86% 18%, rgba(215,169,40,.20) 0 2px, transparent 3px),
      radial-gradient(circle at 76% 72%, rgba(255,255,255,.55) 0 3px, transparent 4px),
      linear-gradient(135deg,#EAF7EE 0%,#F8FCF9 52%,#E7F2EA 100%);
    box-shadow:0 16px 38px rgba(6,76,37,.10), inset 0 1px 0 rgba(255,255,255,.85);
  }
  .polls-hero:before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    right:-90px;top:-115px;
    border:2px solid rgba(6,92,45,.12);
    border-radius:50%;
    box-shadow:
      0 0 0 22px rgba(6,92,45,.045),
      0 0 0 44px rgba(215,169,40,.055),
      0 0 0 66px rgba(6,92,45,.035);
    transform:rotate(-18deg);
    pointer-events:none;
  }
  .polls-hero:after{
    content:'';
    position:absolute;
    left:-35px;bottom:-72px;
    width:210px;height:140px;
    background:
      repeating-linear-gradient(135deg,
        rgba(6,92,45,.08) 0 2px,
        transparent 2px 15px);
    transform:skewX(-18deg);
    opacity:.7;
    pointer-events:none;
  }
  .polls-hero > *{position:relative;z-index:2;}
  .polls-hero .eyebrow,
  .polls-hero .hero-eyebrow{
    color:#0B7A3B !important;
    letter-spacing:1.2px;
    font-weight:800;
  }
  .polls-hero h1,.polls-hero h2,.polls-hero h3{
    color:#064C25;
  }
  .polls-hero p{color:#4E6657;}
  .polls-hero .btn-primary,
  .polls-hero .btn-green{
    background:linear-gradient(135deg,#075B2A,#0B7A3B);
    border-color:#075B2A;
    color:#fff;
    box-shadow:0 7px 18px rgba(7,91,42,.22);
  }
  .polls-hero .btn-primary:hover,
  .polls-hero .btn-green:hover{
    background:linear-gradient(135deg,#064C25,#096B34);
    transform:translateY(-1px);
  }
  .poll-option{
    border-color:rgba(7,91,42,.14);
    background:rgba(255,255,255,.78);
    box-shadow:0 5px 16px rgba(6,76,37,.055);
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }
  .poll-option:hover{
    transform:translateY(-2px);
    border-color:rgba(7,91,42,.32);
    box-shadow:0 9px 22px rgba(6,76,37,.10);
  }
  .poll-option.voted{
    border-color:#0B7A3B;
    background:#EDF8F0;
    box-shadow:0 7px 18px rgba(7,91,42,.10);
  }
  .poll-bar{background:#DCEBE0;}
  .poll-bar-fill{
    background:linear-gradient(90deg,#064C25,#0B7A3B 72%,#D7A928);
    box-shadow:0 1px 5px rgba(7,91,42,.18);
  }
  .vote-btn{
    border-color:rgba(7,91,42,.15);
    background:rgba(255,255,255,.84);
  }
  .vote-btn:hover{
    border-color:#0B7A3B;
    background:#EDF8F0;
    color:#064C25;
  }
  .poll-stat,.poll-card,.survey-card{
    border-color:rgba(7,91,42,.14);
    box-shadow:0 9px 24px rgba(6,76,37,.07);
  }
  .poll-status-active,.survey-status-active{
    background:#E7F6EB !important;
    color:#075B2A !important;
    border-color:#BFE3C8 !important;
  }
  .polls-page .card,
  .polls-page .panel{
    border-color:rgba(7,91,42,.13);
  }
  @media (prefers-reduced-motion:no-preference){
    .polls-hero:before{
      animation:nbsc-archer-glow 8s ease-in-out infinite alternate;
    }
    @keyframes nbsc-archer-glow{
      from{transform:rotate(-18deg) scale(1);}
      to{transform:rotate(-12deg) scale(1.04);}
    }
  }


/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>

<style>
/* ===== Poll officer management ===== */
.poll-card-footer-main{display:flex;align-items:center;justify-content:space-between;gap:12px}
.poll-view-link{display:inline-flex;align-items:center;min-height:32px;text-decoration:none;color:inherit}
.poll-manage-actions{display:flex;align-items:center;gap:7px}
.poll-manage-actions form{margin:0}
.poll-action-btn{display:grid;place-items:center;width:32px;height:32px;border:1px solid #DCE4DE;border-radius:9px;background:#fff;color:#66716A;text-decoration:none;cursor:pointer;font:700 15px/1 inherit;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease,color .18s ease,background .18s ease}
.poll-action-btn:hover{transform:translateY(-2px);box-shadow:0 7px 16px rgba(20,33,61,.10);border-color:#BFD1C5}
.poll-edit:hover{color:#1F8B4C;background:#F4FBF6}
.poll-delete:hover{color:#B33B3B;background:#FFF4F4;border-color:#E7BABA}
.poll-detail-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:18px;padding-top:15px;border-top:1px solid #EEF1EF}
.poll-detail-actions form{margin:0}
.poll-detail-delete{border:1px solid #E2B7B7!important;background:#FFF7F7!important;color:#B33B3B!important}
.poll-detail-delete:hover{background:#FFF0F0!important;border-color:#D99898!important}
.poll-edit-panel{margin-top:17px;padding:18px;border:1px solid #DCE7DF;border-radius:15px;background:linear-gradient(145deg,#FBFEFC,#FFFFFF);box-shadow:0 10px 24px rgba(20,33,61,.06);animation:pollEditIn .22s ease-out}
.poll-edit-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;margin-bottom:14px}
.poll-edit-panel-head strong{display:block;font-size:16px;color:var(--ink)}
.poll-edit-panel-head span{display:block;margin-top:4px;font-size:11.5px;line-height:1.5;color:var(--slate)}
.poll-edit-close{display:grid;place-items:center;width:30px;height:30px;border-radius:8px;text-decoration:none;color:#78827B;background:#F3F6F4;font-size:19px;transition:transform .18s ease,background .18s ease}
.poll-edit-close:hover{transform:rotate(90deg);background:#EAF2EC;color:#1F8B4C}
.poll-edit-panel input:disabled{background:#F1F3F2;color:#7D867F;cursor:not-allowed}
@keyframes pollEditIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
@media (max-width:600px){.poll-card-footer-main{align-items:flex-start}.poll-detail-actions{align-items:stretch}.poll-detail-actions .btn,.poll-detail-actions form{width:100%}.poll-detail-actions form .btn{width:100%}}
@media (prefers-reduced-motion:reduce){.poll-action-btn,.poll-edit-close,.poll-edit-panel{transition:none;animation:none}}

/* ===== Personal information: polished profile experience ===== */
.profile-back{display:inline-flex;align-items:center;gap:7px;margin-bottom:18px;font-weight:650;color:var(--ink-2);transition:transform .2s ease,color .2s ease}.profile-back svg{width:18px;height:18px}.profile-back:hover{transform:translateX(-3px);color:var(--ink)}
.profile-page-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:24px}.profile-kicker{font-size:10px;font-weight:850;letter-spacing:1.8px;color:#1F8B4C;margin-bottom:7px}.profile-page-heading h2{font-size:34px;margin:0;letter-spacing:-.8px;color:#075B2A}.heading-sparkle{display:inline-block;color:#EFB23D;font-size:.75em;margin-left:5px;animation:profileSparkle 2.8s ease-in-out infinite}.profile-heading-badge{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border-radius:999px;background:#EAF7EF;border:1px solid #D3EBDD;color:#1F8B4C;font-size:11px;font-weight:750;box-shadow:0 6px 18px rgba(31,139,76,.08)}.profile-heading-badge svg{width:16px;height:16px}
.personal-profile-card{position:relative;overflow:hidden;padding:30px;border:1px solid #DDE7E1;border-radius:22px;background:linear-gradient(135deg,#F8FCF9 0%,#FFFFFF 56%,#FFFBF1 100%);box-shadow:0 18px 42px rgba(20,33,61,.08);isolation:isolate}.personal-profile-card:before{content:"";position:absolute;inset:0;background-image:radial-gradient(#DCEEE2 1.3px,transparent 1.3px);background-size:17px 17px;opacity:.34;mask-image:linear-gradient(90deg,#000,transparent 78%);pointer-events:none}.personal-profile-bg{position:absolute;border-radius:50%;pointer-events:none;z-index:-1;filter:blur(2px)}.personal-bg-one{width:280px;height:280px;right:-90px;top:-125px;background:rgba(31,139,76,.08)}.personal-bg-two{width:190px;height:190px;right:190px;bottom:-130px;background:rgba(239,178,61,.09)}
.profile-identity-row{position:relative;display:grid;grid-template-columns:190px minmax(230px,1fr) auto;align-items:center;gap:25px;padding:3px 4px 30px}.profile-avatar-wrap{position:relative;width:154px;height:154px;margin:auto}.profile-avatar-ring{width:154px;height:154px;display:grid;place-items:center;border-radius:50%;padding:6px;background:linear-gradient(135deg,#BCEACB,#1F8B4C 52%,#EFB23D);box-shadow:0 10px 30px rgba(31,139,76,.20);animation:profileAvatarFloat 4s ease-in-out infinite}.profile-avatar-ring > *{width:142px!important;height:142px!important;border:5px solid #fff!important;box-shadow:none!important}.profile-camera-badge{position:absolute;right:-2px;bottom:9px;width:42px;height:42px;display:grid;place-items:center;border-radius:50%;background:#087A3B;color:#fff;border:4px solid #fff;box-shadow:0 8px 18px rgba(7,91,42,.22)}.profile-camera-badge svg{width:18px;height:18px}.profile-sparkle{position:absolute;color:#EFB23D;font-size:17px;animation:profileSparkle 2.5s ease-in-out infinite}.sparkle-one{left:-11px;top:25px}.sparkle-two{right:4px;top:2px;font-size:11px;animation-delay:.8s}.profile-identity-main h3{font-family:'Space Grotesk',sans-serif;font-size:28px;margin:0 0 8px;color:#101A16;letter-spacing:-.5px}.profile-role-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border-radius:999px;background:#EAF7EF;color:#146E3B;font-weight:750;font-size:12px}.profile-role-pill svg{width:16px;height:16px}.profile-identity-main p{margin:11px 0 0;color:var(--slate);font-size:12px;line-height:1.55;max-width:430px}
.profile-photo-form{display:flex;flex-direction:column;gap:10px;min-width:300px}.profile-file-picker{height:58px;display:flex;align-items:center;gap:9px;padding:0 14px;border:1px dashed #B8CDBE;border-radius:13px;background:rgba(255,255,255,.74);color:#66716A;font-size:12px;font-weight:600;cursor:pointer;transition:.2s ease}.profile-file-picker:hover{border-color:#1F8B4C;background:#F7FCF8;box-shadow:0 8px 18px rgba(31,139,76,.07)}.profile-file-picker svg{width:19px;color:#1F8B4C}.profile-file-picker input{display:none}.profile-update-photo{min-height:48px;border-radius:13px;box-shadow:0 8px 20px rgba(7,91,42,.16);transition:transform .2s ease,box-shadow .2s ease}.profile-update-photo:hover{transform:translateY(-2px);box-shadow:0 12px 25px rgba(7,91,42,.22)}.profile-update-photo svg{width:17px}
.profile-info-grid{position:relative;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin-top:2px}.profile-info-item{display:flex;align-items:center;gap:15px;min-height:88px;padding:16px 18px;border:1px solid #E5ECE7;border-radius:15px;background:rgba(255,255,255,.9);box-shadow:0 6px 17px rgba(20,33,61,.045);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}.profile-info-item:hover{transform:translateY(-3px);border-color:#CFE0D5;box-shadow:0 12px 24px rgba(20,33,61,.08)}.profile-info-icon{width:48px;height:48px;flex:0 0 48px;display:grid;place-items:center;border-radius:50%;background:#EDF7F0;color:#1F8B4C}.profile-info-icon svg{width:21px;height:21px}.profile-info-item .k{font-size:10px;letter-spacing:1px;color:#65726A;font-weight:750;margin-bottom:4px}.profile-info-item .v{font-size:15px;color:#121A16;font-weight:750;line-height:1.35;overflow-wrap:anywhere}.profile-info-item .mono{font-family:'JetBrains Mono',monospace;font-size:13px}
.profile-info-notice{position:relative;display:flex;align-items:center;gap:14px;margin-top:16px;padding:17px 19px;border:1px solid #DCEBE1;border-radius:16px;background:linear-gradient(100deg,#F0F9F3,#F9FCFA);overflow:hidden}.profile-info-notice:after{content:"";position:absolute;width:100px;height:100px;right:20px;top:-60px;border-radius:50%;background:rgba(239,178,61,.11)}.notice-icon{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;border-radius:50%;background:#4DAA72;color:#fff}.notice-icon svg{width:19px}.profile-info-notice strong{font-size:12.5px;color:#183D28}.profile-info-notice p{margin:4px 0 0;font-size:11.5px;color:var(--slate);line-height:1.5}.notice-check{margin-left:auto;width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:#E0F1E5;color:#1F8B4C;z-index:1}.notice-check svg{width:17px}
.officer-profile-card{margin-top:18px;padding:24px;border:1px solid #DDE7E1;border-radius:20px;background:#fff;box-shadow:0 12px 28px rgba(20,33,61,.06)}.officer-profile-heading{display:flex;justify-content:space-between;gap:15px;align-items:flex-start;margin-bottom:17px}.officer-profile-heading h3{margin:0;font-size:22px;color:#075B2A}.officer-profile-heading p{margin:6px 0 0;color:var(--slate);font-size:12px}.officer-profile-icon{width:46px;height:46px;display:grid;place-items:center;border-radius:13px;background:#FFF4D9;color:#B47C14}.officer-profile-icon svg{width:21px}.officer-profile-form{display:grid;grid-template-columns:1fr 1.5fr auto;gap:13px;align-items:end}.officer-profile-form .field{margin:0}.officer-profile-form textarea{min-height:42px}
@keyframes profileAvatarFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}@keyframes profileSparkle{0%,100%{opacity:.45;transform:scale(.9) rotate(0)}50%{opacity:1;transform:scale(1.12) rotate(12deg)}}
@media(max-width:900px){.profile-identity-row{grid-template-columns:145px 1fr}.profile-photo-form{grid-column:1/-1;display:grid;grid-template-columns:1fr auto}.profile-info-grid{grid-template-columns:1fr}.officer-profile-form{grid-template-columns:1fr}.profile-heading-badge{display:none}}@media(max-width:600px){.personal-profile-card{padding:20px}.profile-page-heading h2{font-size:28px}.profile-identity-row{grid-template-columns:1fr;text-align:center}.profile-identity-main p{margin-left:auto;margin-right:auto}.profile-photo-form{display:flex;min-width:0;text-align:left}.profile-info-item{min-height:78px;padding:13px}.profile-info-notice{align-items:flex-start}.notice-check{display:none}}@media(prefers-reduced-motion:reduce){.profile-avatar-ring,.profile-sparkle,.heading-sparkle{animation:none}.profile-info-item,.profile-update-photo,.profile-file-picker,.profile-back{transition:none}}


  /* ===== NBSC Green Archers theme for Polls & Surveys ===== */
  .polls-hero{
    border:1px solid rgba(6,92,45,.18);
    background:
      radial-gradient(circle at 86% 18%, rgba(215,169,40,.20) 0 2px, transparent 3px),
      radial-gradient(circle at 76% 72%, rgba(255,255,255,.55) 0 3px, transparent 4px),
      linear-gradient(135deg,#EAF7EE 0%,#F8FCF9 52%,#E7F2EA 100%);
    box-shadow:0 16px 38px rgba(6,76,37,.10), inset 0 1px 0 rgba(255,255,255,.85);
  }
  .polls-hero:before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    right:-90px;top:-115px;
    border:2px solid rgba(6,92,45,.12);
    border-radius:50%;
    box-shadow:
      0 0 0 22px rgba(6,92,45,.045),
      0 0 0 44px rgba(215,169,40,.055),
      0 0 0 66px rgba(6,92,45,.035);
    transform:rotate(-18deg);
    pointer-events:none;
  }
  .polls-hero:after{
    content:'';
    position:absolute;
    left:-35px;bottom:-72px;
    width:210px;height:140px;
    background:
      repeating-linear-gradient(135deg,
        rgba(6,92,45,.08) 0 2px,
        transparent 2px 15px);
    transform:skewX(-18deg);
    opacity:.7;
    pointer-events:none;
  }
  .polls-hero > *{position:relative;z-index:2;}
  .polls-hero .eyebrow,
  .polls-hero .hero-eyebrow{
    color:#0B7A3B !important;
    letter-spacing:1.2px;
    font-weight:800;
  }
  .polls-hero h1,.polls-hero h2,.polls-hero h3{
    color:#064C25;
  }
  .polls-hero p{color:#4E6657;}
  .polls-hero .btn-primary,
  .polls-hero .btn-green{
    background:linear-gradient(135deg,#075B2A,#0B7A3B);
    border-color:#075B2A;
    color:#fff;
    box-shadow:0 7px 18px rgba(7,91,42,.22);
  }
  .polls-hero .btn-primary:hover,
  .polls-hero .btn-green:hover{
    background:linear-gradient(135deg,#064C25,#096B34);
    transform:translateY(-1px);
  }
  .poll-option{
    border-color:rgba(7,91,42,.14);
    background:rgba(255,255,255,.78);
    box-shadow:0 5px 16px rgba(6,76,37,.055);
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }
  .poll-option:hover{
    transform:translateY(-2px);
    border-color:rgba(7,91,42,.32);
    box-shadow:0 9px 22px rgba(6,76,37,.10);
  }
  .poll-option.voted{
    border-color:#0B7A3B;
    background:#EDF8F0;
    box-shadow:0 7px 18px rgba(7,91,42,.10);
  }
  .poll-bar{background:#DCEBE0;}
  .poll-bar-fill{
    background:linear-gradient(90deg,#064C25,#0B7A3B 72%,#D7A928);
    box-shadow:0 1px 5px rgba(7,91,42,.18);
  }
  .vote-btn{
    border-color:rgba(7,91,42,.15);
    background:rgba(255,255,255,.84);
  }
  .vote-btn:hover{
    border-color:#0B7A3B;
    background:#EDF8F0;
    color:#064C25;
  }
  .poll-stat,.poll-card,.survey-card{
    border-color:rgba(7,91,42,.14);
    box-shadow:0 9px 24px rgba(6,76,37,.07);
  }
  .poll-status-active,.survey-status-active{
    background:#E7F6EB !important;
    color:#075B2A !important;
    border-color:#BFE3C8 !important;
  }
  .polls-page .card,
  .polls-page .panel{
    border-color:rgba(7,91,42,.13);
  }
  @media (prefers-reduced-motion:no-preference){
    .polls-hero:before{
      animation:nbsc-archer-glow 8s ease-in-out infinite alternate;
    }
    @keyframes nbsc-archer-glow{
      from{transform:rotate(-18deg) scale(1);}
      to{transform:rotate(-12deg) scale(1.04);}
    }
  }


/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* Event organizer management + event photo controls */
.organizer-list{display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;width:100%;}
.event-organizer-manage{border:1px solid var(--line);border-radius:14px;background:var(--paper-2);padding:12px;display:grid;gap:8px}
.manage-organizer-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff}
.manage-organizer-row>div{display:flex;flex-direction:column;gap:2px;min-width:0}
.manage-organizer-row span{font-size:11px;color:var(--slate)}
.manage-empty{font-size:12px;color:var(--slate);padding:4px}
.add-organizer-row{display:flex;gap:8px;align-items:center;margin-top:2px}
.add-organizer-row select{flex:1;min-width:0}
.add-organizer-row input[type="text"]{flex:1;min-width:0}
.photo-actions{position:absolute;left:8px;right:8px;bottom:8px;display:flex;gap:6px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.photo-action-btn,.cover-photo-badge{border:0;border-radius:8px;padding:6px 9px;font-size:10px;font-weight:700;background:rgba(255,255,255,.94);color:#1F8B4C;box-shadow:0 3px 10px rgba(20,33,61,.12);cursor:pointer}
.cover-photo-badge{background:#EFB23D;color:#fff;cursor:default}
.photo-grid .ph{position:relative;overflow:hidden}
@media(max-width:650px){.add-organizer-row{flex-direction:column;align-items:stretch}.manage-organizer-row{align-items:flex-start}.photo-actions{position:static;padding:8px;background:#fff}.photo-grid .ph{overflow:visible}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>

<style>
/* Officer event media management */
.event-media-manage{border:1px solid var(--line);border-radius:14px;background:var(--paper-2);padding:12px;display:grid;gap:10px}
.event-media-upload{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.event-media-upload input[type=file]{flex:1;min-width:220px}
.event-manage-photo-grid{margin:0}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>

<style>
/* Dedicated event profile/cover photo management */
.event-cover-manage{border:1px solid var(--line);border-radius:14px;background:var(--paper-2);padding:14px;display:grid;gap:12px}
.event-cover-preview{position:relative;overflow:hidden;border-radius:12px;min-height:180px;background:#fff;border:1px solid var(--line)}
.event-cover-preview img{display:block;width:100%;height:220px;object-fit:cover}
.event-cover-preview .cover-photo-badge{position:absolute;left:10px;bottom:10px}
.event-cover-empty{border:1px dashed var(--line);border-radius:12px;background:#fff;padding:34px 16px;text-align:center;color:var(--slate);font-size:12px}
.event-cover-upload{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.event-cover-upload input[type=file]{flex:1;min-width:220px}
@media(max-width:650px){.event-cover-upload{align-items:stretch;flex-direction:column}.event-cover-preview img{height:170px}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>

<style>
/* My Schedule — engaging student calendar */
.schedule-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:24px 28px;margin-bottom:16px;border-radius:20px;color:#fff;background:linear-gradient(135deg,#064c25,#08733a 58%,#159a50);box-shadow:0 14px 30px rgba(5,84,38,.16);position:relative;overflow:hidden}
.schedule-hero:after{content:'';position:absolute;width:220px;height:220px;right:-70px;top:-110px;border:28px solid rgba(255,255,255,.07);border-radius:50%;pointer-events:none}
.schedule-kicker,.schedule-mini-label{display:block;font-size:10px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}
.schedule-kicker{color:#bce6c7;margin-bottom:5px}.schedule-hero h2{margin:0;font-size:25px}.schedule-hero p{margin:5px 0 0;color:rgba(255,255,255,.84);font-size:13px;max-width:650px}.schedule-hero-icon{width:58px;height:58px;border-radius:18px;background:rgba(255,255,255,.13);display:flex;align-items:center;justify-content:center;flex-shrink:0}.schedule-hero-icon svg{width:30px;height:30px}
.schedule-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}.schedule-stat{display:flex;align-items:center;gap:12px;padding:14px 16px;border:1px solid #e1ebe5;background:rgba(255,255,255,.7);border-radius:14px;box-shadow:0 5px 15px rgba(10,70,40,.05)}.schedule-stat-icon{width:38px;height:38px;border-radius:11px;background:#e5f4e9;color:#08733a;display:flex;align-items:center;justify-content:center}.schedule-stat-icon svg{width:18px;height:18px}.schedule-stat strong{display:block;font-size:20px;line-height:1.05;color:#083d23}.schedule-stat span{font-size:11px;color:var(--slate)}
.schedule-layout{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(280px,.8fr);gap:16px;align-items:start}.schedule-calendar-card,.schedule-day-card{padding:18px}.schedule-calendar-head,.schedule-day-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.schedule-calendar-head h3,.schedule-day-head h3{margin:2px 0 0;font-size:19px}.schedule-mini-label{color:#08733a}.schedule-nav{display:flex;gap:6px}.schedule-legend{display:flex;gap:14px;flex-wrap:wrap;align-items:center;margin:12px 0 8px;font-size:10.5px;color:var(--slate)}.schedule-legend span{display:inline-flex;align-items:center;gap:5px}.legend-dot{width:8px;height:8px;border-radius:50%;display:inline-block}.legend-dot.event{background:#08733a}.legend-dot.today{background:#d9a928;box-shadow:0 0 0 2px #f5e5af}
.schedule-cal-grid{gap:6px}.schedule-cal-day{aspect-ratio:auto;min-height:102px;padding:8px;background:#f8fbf9;border:1px solid #e5eee8;border-radius:11px;overflow:hidden}.schedule-cal-day:hover{background:#eef8f1;transform:translateY(-1px)}.schedule-cal-day.has-event{background:#e8f6ed;border-color:#bfe2cb}.schedule-cal-day.today{outline:2px solid #d9a928;outline-offset:-2px}.schedule-cal-day.selected{background:#07552d;color:#fff;border-color:#07552d}.schedule-cal-day.selected .calendar-event-list span{background:rgba(255,255,255,.13);color:#fff}.schedule-cal-day.selected .calendar-event-count{color:#bce6c7}.cal-number{display:block;font-size:12px;font-weight:800;margin-bottom:5px}.calendar-event-count{display:block;font-size:9px;color:#08733a;font-weight:800;margin-bottom:4px}.calendar-event-list{display:flex;flex-direction:column;gap:3px}.calendar-event-list span{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;background:#fff;border-radius:5px;padding:3px 5px;color:#17432b;font-size:9.5px;font-weight:600}.calendar-event-list .calendar-more{background:transparent;padding:1px 2px;color:#08733a}.schedule-cal-day.selected .calendar-event-list .calendar-more{color:#bce6c7}
.schedule-day-head{padding-bottom:13px;border-bottom:1px solid #e7eee9}.schedule-day-head p{margin:2px 0 0;color:var(--slate);font-size:11px}.day-count{min-width:46px;height:46px;border-radius:14px;background:#e8f6ed;color:#08733a;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:19px;font-weight:800}.day-count small{font-size:8px;text-transform:uppercase;letter-spacing:.5px}.schedule-event-card{display:flex;align-items:center;gap:10px;padding:13px 4px;position:relative;text-decoration:none;color:inherit;border-bottom:1px solid #edf1ee;transition:transform .2s ease,background .2s ease;padding-left:8px;border-radius:9px}.schedule-event-card:hover{background:#f6faf7;transform:translateX(3px)}.schedule-event-accent{width:4px;align-self:stretch;border-radius:5px;flex-shrink:0}.schedule-event-time{width:56px;font-size:10px;font-weight:800;color:var(--slate);flex-shrink:0}.schedule-event-content{min-width:0;flex:1}.schedule-event-content h4{margin:0 0 5px;font-size:12.5px;line-height:1.25;color:#183c28}.schedule-event-content>div{display:flex;gap:7px;flex-wrap:wrap;align-items:center;font-size:10px;color:var(--slate)}.schedule-event-content svg{width:11px;height:11px;vertical-align:-2px}.schedule-category{padding:2px 6px;border-radius:99px;background:#edf4ef;color:#08733a;font-weight:700}.schedule-event-arrow{font-size:16px;color:#08733a}.schedule-empty{text-align:center;padding:28px 12px;color:var(--slate)}.schedule-empty-icon{width:44px;height:44px;margin:0 auto 10px;border-radius:14px;background:#eef7f1;color:#08733a;display:flex;align-items:center;justify-content:center}.schedule-empty-icon svg{width:21px}.schedule-empty strong{display:block;color:#183c28;font-size:13px}.schedule-empty p{font-size:11px;line-height:1.5;margin:5px auto 12px;max-width:230px}
@media (max-width:900px){.schedule-layout{grid-template-columns:1fr}.schedule-cal-day{min-height:82px}.schedule-day-card{min-height:0}}
@media (max-width:620px){.schedule-hero{padding:20px;border-radius:16px}.schedule-hero h2{font-size:22px}.schedule-hero-icon{display:none}.schedule-stats{grid-template-columns:1fr}.schedule-stat{padding:11px 13px}.schedule-cal-day{min-height:65px;padding:6px}.calendar-event-list span{font-size:8px;padding:2px 3px}.calendar-event-list span:nth-child(n+2){display:none}.schedule-legend{gap:8px}.schedule-calendar-head{align-items:flex-start}.schedule-nav .btn{padding:7px 9px}.schedule-calendar-card,.schedule-day-card{padding:13px}}

/* Officer dashboard */
.officer-dashboard-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:28px 30px;margin-bottom:18px;border-radius:22px;color:#fff;background:linear-gradient(135deg,#064c25,#08733a 58%,#159a50);box-shadow:0 16px 34px rgba(5,84,38,.16);position:relative;overflow:hidden}
.officer-dashboard-hero:after{content:'';position:absolute;width:260px;height:260px;right:-90px;top:-130px;border:30px solid rgba(255,255,255,.07);border-radius:50%;pointer-events:none}
.officer-dashboard-kicker{font-size:10px;font-weight:800;letter-spacing:1.3px;color:#bce6c7;margin-bottom:7px}
.officer-dashboard-hero h2{margin:0;font-size:27px;letter-spacing:-.4px}.officer-dashboard-hero p{margin:6px 0 16px;color:rgba(255,255,255,.86);font-size:13px;max-width:680px;line-height:1.55}
.officer-dashboard-actions{display:flex;gap:9px;flex-wrap:wrap}.officer-ghost-btn{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.18)}.officer-ghost-btn:hover{background:rgba(255,255,255,.18);color:#fff}
.officer-dashboard-mark{width:72px;height:72px;border-radius:22px;background:rgba(255,255,255,.13);display:flex;align-items:center;justify-content:center;flex-shrink:0}.officer-dashboard-mark svg{width:36px;height:36px}
.officer-dashboard-stats{margin-bottom:18px}.officer-dashboard-stats .stat{min-height:112px}
.officer-dashboard-grid{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(270px,.7fr);gap:18px;align-items:start}.officer-dashboard-side{display:flex;flex-direction:column;gap:18px}.officer-quick-card{margin:0!important}.officer-quick-icon{width:42px;height:42px;border-radius:13px;background:#e8f6ed;color:#08733a;display:flex;align-items:center;justify-content:center;margin-bottom:11px}.officer-quick-icon svg{width:20px;height:20px}.officer-quick-card h3{margin:0 0 7px;font-size:15px}.officer-quick-card p{font-size:12px;line-height:1.6;color:var(--slate);margin:0 0 13px}
.officer-section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px}.officer-section-kicker{font-size:9px;font-weight:800;letter-spacing:1.2px;color:#08733a}.officer-section-head h3{margin:2px 0 3px;font-size:18px;color:#164E31}.officer-section-head p{margin:0;color:var(--slate);font-size:11px}.officer-view-link{font-size:12px;font-weight:700;color:#08733a;white-space:nowrap}.officer-empty{text-align:center;padding:30px 15px}.officer-empty-icon{width:46px;height:46px;margin:0 auto 10px;border-radius:14px;background:#eef7f1;color:#08733a;display:flex;align-items:center;justify-content:center}.officer-empty-icon svg{width:22px}.officer-empty strong{display:block;color:#183c28;font-size:13px}.officer-empty p{font-size:11px;color:var(--slate);margin:5px auto 12px;max-width:250px;line-height:1.5}.officer-past-card{margin-top:18px}
@media (max-width:900px){.officer-dashboard-grid{grid-template-columns:1fr}.officer-dashboard-side{display:grid;grid-template-columns:1fr 1fr}.officer-dashboard-mark{display:none}}
@media (max-width:620px){.officer-dashboard-hero{padding:22px;border-radius:17px}.officer-dashboard-hero h2{font-size:23px}.officer-dashboard-stats{grid-template-columns:1fr 1fr}.officer-dashboard-stats .stat{min-height:100px}.officer-dashboard-side{display:block}.officer-dashboard-side .quick-card + .quick-card{margin-top:14px!important}.officer-section-head{align-items:flex-start}}
@media (max-width:430px){.officer-dashboard-stats{grid-template-columns:1fr}.officer-dashboard-actions .btn{width:100%;text-align:center}}

/* Officer dashboard */
.officer-dashboard-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:28px 30px;margin-bottom:18px;border-radius:22px;color:#fff;background:linear-gradient(135deg,#064c25,#08733a 58%,#159a50);box-shadow:0 16px 34px rgba(5,84,38,.16);position:relative;overflow:hidden}.officer-dashboard-hero:after{content:'';position:absolute;width:260px;height:260px;right:-90px;top:-130px;border:30px solid rgba(255,255,255,.07);border-radius:50%;pointer-events:none}.officer-dashboard-kicker{font-size:10px;font-weight:800;letter-spacing:1.3px;color:#bce6c7;margin-bottom:7px}.officer-dashboard-hero h2{margin:0;font-size:27px;letter-spacing:-.4px}.officer-dashboard-hero p{margin:6px 0 16px;color:rgba(255,255,255,.86);font-size:13px;max-width:680px;line-height:1.55}.officer-dashboard-actions{display:flex;gap:9px;flex-wrap:wrap}.officer-ghost-btn{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.18)}.officer-ghost-btn:hover{background:rgba(255,255,255,.18);color:#fff}.officer-dashboard-mark{width:72px;height:72px;border-radius:22px;background:rgba(255,255,255,.13);display:flex;align-items:center;justify-content:center;flex-shrink:0}.officer-dashboard-mark svg{width:36px;height:36px}.officer-dashboard-stats{margin-bottom:18px}.officer-dashboard-stats .stat{min-height:112px}.officer-dashboard-grid{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(270px,.7fr);gap:18px;align-items:start}.officer-dashboard-side{display:flex;flex-direction:column;gap:18px}.officer-quick-card{margin:0!important}.officer-quick-icon{width:42px;height:42px;border-radius:13px;background:#e8f6ed;color:#08733a;display:flex;align-items:center;justify-content:center;margin-bottom:11px}.officer-quick-icon svg{width:20px;height:20px}.officer-quick-card h3{margin:0 0 7px;font-size:15px}.officer-quick-card p{font-size:12px;line-height:1.6;color:var(--slate);margin:0 0 13px}.officer-section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px}.officer-section-kicker{font-size:9px;font-weight:800;letter-spacing:1.2px;color:#08733a}.officer-section-head h3{margin:2px 0 3px;font-size:18px;color:#164E31}.officer-section-head p{margin:0;color:var(--slate);font-size:11px}.officer-view-link{font-size:12px;font-weight:700;color:#08733a;white-space:nowrap}.officer-empty{text-align:center;padding:30px 15px}.officer-empty-icon{width:46px;height:46px;margin:0 auto 10px;border-radius:14px;background:#eef7f1;color:#08733a;display:flex;align-items:center;justify-content:center}.officer-empty-icon svg{width:22px}.officer-empty strong{display:block;color:#183c28;font-size:13px}.officer-empty p{font-size:11px;color:var(--slate);margin:5px auto 12px;max-width:250px;line-height:1.5}.officer-past-card{margin-top:18px}@media (max-width:900px){.officer-dashboard-grid{grid-template-columns:1fr}.officer-dashboard-side{display:grid;grid-template-columns:1fr 1fr}.officer-dashboard-mark{display:none}}@media (max-width:620px){.officer-dashboard-hero{padding:22px;border-radius:17px}.officer-dashboard-hero h2{font-size:23px}.officer-dashboard-stats{grid-template-columns:1fr 1fr}.officer-dashboard-stats .stat{min-height:100px}.officer-dashboard-side{display:block}.officer-dashboard-side .quick-card + .quick-card{margin-top:14px!important}}@media (max-width:430px){.officer-dashboard-stats{grid-template-columns:1fr}.officer-dashboard-actions .btn{width:100%;text-align:center}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>


<style>
.poll-choice-input{display:grid;grid-template-columns:minmax(0,1fr) minmax(180px,230px);gap:8px;margin-bottom:9px;align-items:center}.poll-choice-input input[type=file]{width:100%;font-size:12px}.poll-choice-thumb{width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--line)}.poll-choice-display{width:48px;height:48px;object-fit:cover;border-radius:9px;border:1px solid var(--line);display:inline-block;margin-right:9px;vertical-align:middle}.poll-option-label{display:flex;align-items:center;gap:2px}.vote-choice-content{display:flex;align-items:center;gap:8px;text-align:left}@media(max-width:650px){.poll-choice-input{grid-template-columns:1fr}.poll-choice-display{width:42px;height:42px}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* Green archer artwork replaces the old floating diamond decorations */
.archer-icon{display:block;width:1em;height:1em;object-fit:contain;user-select:none;-webkit-user-drag:none}
.profile-sparkle{line-height:1;opacity:1;animation:archerFloat 3.2s ease-in-out infinite}
.profile-sparkle.sparkle-one{left:-26px;top:10px;font-size:40px}
.profile-sparkle.sparkle-two{right:-10px;top:-10px;font-size:28px;animation-delay:.8s}
.polls-hero-icon{font-size:48px}
.poll-modal-icon{background:#EAF7EF;font-size:32px}
@keyframes archerFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
@media (prefers-reduced-motion:reduce){.profile-sparkle{animation:none}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
.user-copy small{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* Adviser dashboard */
.adm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;margin-bottom:18px}
.adm-grid .detail-card{margin:0;max-width:none}
.adm-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-top:1px solid var(--line,#e5e7eb)}
.adm-row:first-of-type{border-top:none}
.adm-row-main{min-width:0;display:flex;flex-direction:column;gap:2px}
.adm-row-main strong{font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.adm-row-main span{font-size:12.5px;color:var(--slate,#5b6472);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.adm-pill{font-size:12px;font-weight:600;color:#1F8B4C;background:#EAF7EF;border-radius:999px;padding:4px 10px;white-space:nowrap}
.adm-empty{padding:18px 4px;color:var(--slate,#5b6472);font-size:13.5px}
.col-chart{display:flex;align-items:flex-end;gap:12px;height:190px;padding:10px 4px 0}
.col-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;min-width:0}
.col-value{font-size:12.5px;font-weight:700;margin-bottom:4px}
.col-bar{width:100%;max-width:46px;background:#1F8B4C;border-radius:6px 6px 0 0;min-height:2px}
.col-label{font-size:12px;color:var(--slate,#5b6472);margin-top:6px}
.bar-row{display:grid;grid-template-columns:96px 1fr 44px;align-items:center;gap:10px;padding:7px 0}
.bar-row-wide{grid-template-columns:minmax(90px,1.1fr) 2fr 82px}
.bar-label{font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bar-track{height:12px;background:#EAF0EC;border-radius:999px;overflow:hidden}
.bar-fill{height:100%;background:#1F8B4C;border-radius:999px;min-width:2px}
.bar-value{font-size:13px;font-weight:700;text-align:right;white-space:nowrap}
.bar-value span{font-weight:400;color:var(--slate,#5b6472);font-size:11.5px}
.adm-feedback{display:flex;gap:30px;flex-wrap:wrap;padding:6px 0}
.adm-feedback .n{font-size:30px;font-weight:700;color:#1F8B4C}
.adm-feedback .l{font-size:12.5px;color:var(--slate,#5b6472)}
@media (max-width:640px){.adm-grid{grid-template-columns:1fr}.bar-row-wide{grid-template-columns:1fr 1fr 70px}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* Activity log */
.log-filter-grid{display:grid;grid-template-columns:2fr 1fr 1.2fr 1fr 1fr;gap:12px}
.log-filters .field{margin:0}
.log-filters select,.log-filters input[type=text],.log-filters input[type=date]{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--line,#e5e7eb);border-radius:10px;background:var(--card,#fff);color:var(--text,#222);font:inherit}
.log-table-wrap{overflow-x:auto;border:1px solid var(--line,#e5e7eb);border-radius:12px;background:var(--card,#fff)}
.log-table{width:100%;border-collapse:collapse;font-size:13.5px;min-width:760px}
.log-table th{text-align:left;padding:11px 12px;font-size:12px;color:var(--slate,#5b6472);background:#f5f8f6;border-bottom:1px solid var(--line,#e5e7eb);white-space:nowrap}
.log-table td{padding:11px 12px;border-bottom:1px solid var(--line,#e5e7eb);vertical-align:top}
.log-table tr:last-child td{border-bottom:none}
.log-table tr.log-failed td{background:#fff6f6}
.log-pager{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap}
@media (max-width:900px){.log-filter-grid{grid-template-columns:1fr 1fr}}
@media (max-width:560px){.log-filter-grid{grid-template-columns:1fr}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>
<style>
/* The logo and the signed-in name are labels only, not links (the panel already has Dashboard and Profile). */
.brand-link,.user-link{cursor:default;}
.brand-link:hover{transform:none;opacity:1;}
.user-link:hover{background:transparent;transform:none;}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>


<style>
/* Dashboard weather and outfit guide — matches the existing SBO Nexus green theme. */
.weather-outfit-card{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(230px,.75fr);gap:0;margin:0 0 22px;overflow:hidden;border:1px solid #dce9df;border-radius:18px;background:var(--card,#fff);box-shadow:0 8px 26px rgba(28,76,47,.07);color:var(--ink,#203329)}
.weather-main{padding:22px 24px 18px;min-width:0}.weather-heading{margin-bottom:16px}.weather-kicker{display:block;color:#27834b;font-size:10px;letter-spacing:1.4px;font-weight:800;margin-bottom:5px}.weather-heading h3{font-size:20px;line-height:1.25;margin:0 0 6px;font-weight:800;letter-spacing:-.35px}.weather-heading p{margin:0;color:var(--slate,#65716a);font-size:12.5px;line-height:1.55}.weather-location-row{display:flex;align-items:center;gap:7px;flex-wrap:wrap;color:var(--slate,#65716a);font-size:12px;font-weight:650}.weather-location-pin{font-size:18px;color:#27834b}.weather-locate-btn{margin-left:auto;background:#edf7ef;border:1px solid #d8eadc;color:#207440;border-radius:8px;padding:7px 10px;font-size:11px;font-weight:750;cursor:pointer}.weather-locate-btn:hover{background:#dff0e3}.weather-current-row{display:flex;align-items:center;gap:16px;padding:18px 0 15px}.weather-symbol{width:76px;height:76px;display:grid;place-items:center;border-radius:20px;background:linear-gradient(145deg,#eef8ef,#e1f1e5);font-size:42px;flex-shrink:0}.weather-temperature{font-size:39px;line-height:1.05;font-weight:800;letter-spacing:-1.5px;color:var(--ink,#203329)}.weather-condition{font-size:14px;font-weight:750;margin-top:5px}.weather-feels{font-size:11.5px;color:var(--slate,#65716a);margin-top:3px}.weather-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));border-top:1px solid #e9efea;padding-top:13px;gap:8px}.weather-metrics div{display:flex;flex-direction:column;gap:4px}.weather-metrics span{font-size:10.5px;color:var(--slate,#65716a)}.weather-metrics strong{font-size:13px;font-weight:800;color:var(--ink,#203329)}.weather-outfit-panel{padding:23px 22px;background:linear-gradient(145deg,#f1f8f1,#e8f4eb);border-left:1px solid #e0ece2;display:flex;flex-direction:column;align-items:flex-start;justify-content:center}.weather-outfit-icon{font-size:30px;margin-bottom:11px}.weather-outfit-label{font-size:9.5px;letter-spacing:1.25px;color:#287c45;font-weight:850;margin-bottom:6px}.weather-outfit-panel h4{font-size:17px;line-height:1.3;margin:0 0 8px;font-weight:800;color:#1c432b}.weather-outfit-panel p{font-size:12px;line-height:1.65;margin:0;color:#405849}.weather-outfit-note{border-top:1px solid #d5e7d8;padding-top:10px;margin-top:13px;color:#52705a;font-size:10.5px;line-height:1.55}.weather-search-row{grid-column:1/-1;border-top:1px solid #e7eee8;padding:12px 20px;background:#fbfdfb;display:grid;grid-template-columns:125px minmax(180px,1fr) minmax(160px,1fr);align-items:center;gap:12px}.weather-search-row label{font-size:11px;font-weight:750;color:var(--ink,#203329)}.weather-search-controls{display:flex;gap:7px;min-width:0}.weather-search-controls input{width:100%;min-width:0;box-sizing:border-box;border:1px solid #dce7de;border-radius:9px;padding:9px 11px;font:inherit;font-size:12px;background:#fff;color:var(--ink,#203329)}.weather-search-controls .btn{white-space:nowrap;padding:8px 14px;font-size:11.5px}.weather-search-controls button:disabled{opacity:.6;cursor:wait}.weather-status{font-size:10.5px;line-height:1.45;color:#68766c}.weather-status.is-error{color:#b42318}
@media(max-width:760px){.weather-outfit-card{grid-template-columns:1fr}.weather-outfit-panel{border-left:0;border-top:1px solid #dce9df;padding:18px 22px}.weather-outfit-icon{margin-bottom:5px}.weather-search-row{grid-template-columns:1fr;gap:7px}.weather-search-controls{width:100%}.weather-status{margin-top:2px}}
@media(max-width:430px){.weather-main{padding:18px 16px 15px}.weather-heading h3{font-size:18px}.weather-current-row{gap:12px}.weather-symbol{width:62px;height:62px;font-size:34px;border-radius:16px}.weather-temperature{font-size:34px}.weather-metrics span{font-size:10px}.weather-outfit-panel{padding:17px 16px}.weather-search-row{padding:12px 14px}.weather-locate-btn{margin-left:0}}

/* Compact weather + outfit dashboard feature */
.weather-mini-card.weather-outfit-card{display:grid!important;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:0!important;margin:14px 0 20px!important;border:1px solid #dce9df!important;border-radius:16px!important;overflow:hidden!important;background:#fff!important;box-shadow:0 4px 16px rgba(25,75,43,.06)!important;max-width:100%!important}
.weather-mini-card .weather-mini-top{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 15px;background:#f1f8f2;border-bottom:1px solid #e1ece3;min-width:0}
.weather-mini-card .weather-mini-label{font-size:10px;font-weight:800;letter-spacing:.09em;color:#287443;white-space:nowrap}
.weather-mini-card .weather-location-row{display:flex;align-items:center;gap:5px;min-width:0;margin:0!important;font-size:11px!important;color:#647568!important}
.weather-mini-card .weather-location-row>span:nth-child(2){overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.weather-mini-card .weather-locate-btn{padding:4px 8px!important;font-size:10px!important;border-radius:7px!important;white-space:nowrap}
.weather-mini-card .weather-main{padding:13px 15px!important;min-width:0!important}
.weather-mini-card .weather-heading{display:none!important}
.weather-mini-card .weather-current-row{display:flex!important;align-items:center!important;gap:11px!important;margin:0 0 10px!important}
.weather-mini-card .weather-symbol{width:48px!important;height:48px!important;min-width:48px!important;font-size:27px!important;border-radius:13px!important;margin:0!important}
.weather-mini-card .weather-temperature{font-size:27px!important;line-height:1.05!important;font-weight:800!important;color:#1c6335!important}
.weather-mini-card .weather-condition{font-size:12px!important;font-weight:700!important;margin-top:3px!important}
.weather-mini-card .weather-feels{font-size:10px!important;margin-top:2px!important;color:#718075!important}
.weather-mini-card .weather-metrics{display:flex!important;gap:12px!important;margin-top:8px!important;padding-top:9px!important;border-top:1px solid #edf2ee!important}
.weather-mini-card .weather-metrics>div{min-width:0!important;flex:1!important}
.weather-mini-card .weather-metrics span{font-size:9px!important;color:#718075!important}
.weather-mini-card .weather-metrics strong{font-size:11px!important;color:#294d34!important}
.weather-mini-card .weather-outfit-panel{display:flex!important;align-items:center!important;gap:11px!important;padding:14px 15px!important;border-left:1px solid #e6eee7!important;border-top:0!important;background:#fbfdfb!important;min-width:0!important}
.weather-mini-card .weather-outfit-icon{font-size:25px!important;flex:0 0 auto!important;margin:0!important}
.weather-mini-card .weather-outfit-copy{min-width:0!important}
.weather-mini-card .weather-outfit-label{font-size:9px!important;letter-spacing:.08em!important;font-weight:800!important;color:#287443!important;margin-bottom:4px!important}
.weather-mini-card .weather-outfit-panel h4{font-size:13px!important;line-height:1.3!important;margin:0 0 4px!important;color:#254b30!important}
.weather-mini-card .weather-outfit-panel p{font-size:11px!important;line-height:1.45!important;margin:0 0 4px!important;color:#526457!important}
.weather-mini-card .weather-outfit-note{font-size:10px!important;line-height:1.4!important;color:#748078!important}
.weather-mini-card .weather-search-row{display:none!important}
@media(max-width:700px){.weather-mini-card.weather-outfit-card{grid-template-columns:1fr!important}.weather-mini-card .weather-mini-top{align-items:flex-start;flex-direction:column;gap:5px}.weather-mini-card .weather-outfit-panel{border-left:0!important;border-top:1px solid #e6eee7!important;padding:12px 15px!important}.weather-mini-card .weather-main{padding:12px 15px!important}}

/* Student Events: compact Upcoming / Past segmented control */
.event-period-tabs{display:flex;align-items:center;gap:4px;width:min(100%,360px);padding:4px;margin:0 0 20px auto;background:rgba(39,116,67,.09);border:1px solid rgba(39,116,67,.10);border-radius:15px}
.event-period-tab{display:flex;align-items:center;justify-content:center;flex:1;min-height:42px;padding:9px 18px;border-radius:11px;color:var(--slate,#66736a);font-weight:600;text-decoration:none;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.event-period-tab:hover{color:var(--green,#287443);background:rgba(255,255,255,.65)}
.event-period-tab.active{color:var(--ink,#17231b);background:#fff;box-shadow:0 2px 8px rgba(23,55,32,.10);border:1px solid rgba(39,116,67,.08)}
@media(max-width:600px){.event-period-tabs{width:100%;margin:0 0 16px}.event-period-tab{min-height:40px}}

</style>

<style>
/* Student Events: compact timeline cards with event posters and attendee avatars */
.tickets:has(.going-event-card){display:flex;flex-direction:column;gap:18px;}
.going-event-card{display:grid;grid-template-columns:minmax(0,1fr) 154px;gap:18px;align-items:center;padding:22px 24px;background:var(--surface,#fff);border:1px solid rgba(36,90,53,.10);border-radius:22px;box-shadow:0 8px 26px rgba(24,54,34,.055);transition:box-shadow .18s ease,transform .18s ease;min-width:0;}
.going-event-card:hover{box-shadow:0 12px 32px rgba(24,54,34,.10);transform:translateY(-1px);}
.going-event-main{display:flex;gap:18px;min-width:0;}
.going-event-date{width:76px;flex:0 0 76px;display:flex;flex-direction:column;gap:4px;padding-top:2px;color:var(--slate,#77827b);}
.going-event-date strong{font-size:17px;line-height:1.2;color:var(--ink,#17231b);white-space:nowrap;}
.going-event-date span{font-size:14px;}
.going-event-content{min-width:0;flex:1;}
.going-event-time{font-size:13px;color:var(--slate,#77827b);margin-bottom:6px;}
.going-event-title{display:block;font-size:clamp(18px,2vw,25px);font-weight:700;line-height:1.2;letter-spacing:-.025em;color:var(--ink,#17231b);text-decoration:none;margin-bottom:10px;overflow-wrap:anywhere;}
.going-event-title:hover{color:var(--green,#287443);}
.going-event-meta{display:flex;gap:8px;align-items:center;color:var(--slate,#77827b);font-size:14px;min-width:0;}
.going-event-meta span{display:flex;align-items:center;gap:7px;min-width:0;}
.going-event-meta svg{width:16px;height:16px;flex:0 0 auto;}
.going-event-bottom{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-top:16px;}
.going-status-pill{display:inline-flex;align-items:center;justify-content:center;padding:7px 14px;border-radius:8px;background:#32b643;color:#fff;font-weight:700;font-size:14px;line-height:1.2;white-space:nowrap;}
.going-status-past{background:#e8eee9;color:#526457;}
.going-people{display:flex;align-items:center;gap:8px;min-width:0;}
.going-avatar-stack{display:flex;align-items:center;padding-left:2px;}
.going-avatar-stack .avatar{width:34px!important;height:34px!important;flex:0 0 34px;border:2px solid #fff;margin-left:-9px;box-shadow:0 1px 3px rgba(0,0,0,.12);object-fit:cover;}
.going-avatar-stack .avatar:first-child{margin-left:0;}
.going-avatar-stack .avatar-fallback{display:inline-flex;align-items:center;justify-content:center;background:#e3efe5;color:#27663a;font-weight:700;}
.going-people-count{font-size:13px;color:#65736a;background:#f0f3f0;border-radius:18px;padding:5px 9px;white-space:nowrap;}
.going-cancel-form{margin-top:12px;}
.going-event-poster{display:block;width:154px;height:154px;border-radius:17px;overflow:hidden;background:#f0f5f1;text-decoration:none;}
.going-event-poster img{display:block;width:100%;height:100%;object-fit:cover;}
.going-event-poster-placeholder{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:#287443;background:linear-gradient(145deg,#e5f2e7,#f7faf7);font-size:38px;}
.going-event-poster-placeholder small{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#4c7156;text-align:center;padding:0 8px;}
@media(max-width:680px){.going-event-card{grid-template-columns:minmax(0,1fr) 96px;gap:12px;padding:16px;border-radius:17px;align-items:start;}.going-event-main{gap:10px;}.going-event-date{width:54px;flex-basis:54px;}.going-event-date strong{font-size:14px;}.going-event-date span{font-size:12px;}.going-event-title{font-size:17px;}.going-event-poster{width:96px;height:96px;border-radius:12px;}.going-event-time,.going-event-meta{font-size:12px;}.going-event-bottom{gap:9px;margin-top:12px;}.going-status-pill{font-size:12px;padding:6px 10px;}.going-avatar-stack .avatar{width:28px!important;height:28px!important;flex-basis:28px;margin-left:-8px;}.going-people-count{font-size:11px;padding:4px 7px;}}
@media(max-width:420px){.going-event-card{grid-template-columns:minmax(0,1fr) 76px;padding:13px;}.going-event-poster{width:76px;height:76px;}.going-event-date{width:46px;flex-basis:46px;}.going-event-main{gap:8px;}.going-event-title{font-size:16px;}}

/* Event details: joined participant avatars, matching the compact overlapping reference */
.detail-joined-item{min-width:0;}
.detail-joined-row{display:flex;align-items:center;gap:0;min-height:40px;overflow:visible;}
.detail-joined-avatars{display:flex;align-items:center;flex-wrap:nowrap;padding-left:2px;min-width:0;}
.detail-joined-avatars .avatar{display:block;width:38px!important;height:38px!important;flex:0 0 38px;border:2px solid #fff;border-radius:50%;object-fit:cover;margin-left:-12px;box-shadow:0 1px 3px rgba(0,0,0,.12);background:#e3efe5;}
.detail-joined-avatars .avatar:first-child{margin-left:0;}
.detail-joined-avatars .avatar-fallback{display:inline-flex;align-items:center;justify-content:center;color:#27663a;font-weight:700;}
.detail-joined-more{display:inline-flex;align-items:center;justify-content:center;margin-left:-3px;padding:6px 10px;min-height:30px;border-radius:20px;background:#f0f3f0;color:#65736a;font-size:13px;line-height:1;white-space:nowrap;}
@media(max-width:520px){.detail-joined-avatars .avatar{width:34px!important;height:34px!important;flex-basis:34px;margin-left:-11px;}.detail-joined-more{font-size:12px;padding:5px 8px;}}

  .detail-about-event{margin-top:18px;}
  .detail-about-event-title{margin:0 0 6px;font-size:16px;font-weight:700;line-height:1.35;color:#000000;}
  .detail-about-event .detail-desc{margin:0;font-size:14px;line-height:1.65;color:var(--slate,#526457);}

  /* Student dashboard summary cards — reference-inspired clean layout */
.dashboard-stat-cards .stat{
  min-height:154px;padding:22px 20px 18px;border-radius:16px;
  background:#fff;border:1px solid #E3EAE6;
  box-shadow:0 10px 28px rgba(25,65,45,.07);
  transition:transform .18s ease,box-shadow .18s ease;
}
.dashboard-stat-cards .stat:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(25,65,45,.11);}
.dashboard-stat-cards .stat-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.dashboard-stat-cards .stat-copy{min-width:0;}
.dashboard-stat-cards .stat .n{font-size:36px;line-height:1.05;color:#075B32;font-weight:750;letter-spacing:-.6px;}
.dashboard-stat-cards .stat .l{font-size:16px;color:#202923;margin-top:6px;}
.dashboard-stat-cards .stat-icon{width:58px;height:58px;border-radius:0;background:transparent!important;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#075B32;}
.dashboard-stat-cards .stat-icon.i-green{background:transparent!important;color:#08713A;}
.dashboard-stat-cards .stat-icon.i-blue{background:transparent!important;color:#14518B;}
.dashboard-stat-cards .stat-icon.i-purple{background:transparent!important;color:#5133A2;}
.dashboard-stat-cards .stat-icon svg{width:28px;height:28px;display:block;stroke:currentColor;fill:none;opacity:1;}
.dashboard-stat-cards .stat-link{font-size:14px;font-weight:700;margin-top:16px;color:#168345;}
@media(max-width:520px){.dashboard-stat-cards .stat{min-height:132px;padding:17px}.dashboard-stat-cards .stat .n{font-size:30px}.dashboard-stat-cards .stat-icon{width:46px;height:46px}}


  .welcome-banner h2 .welcome-name{color:#C8F23A;font-weight:850;}
  .welcome-banner::before{z-index:0;}
  .welcome-banner::after{z-index:1;pointer-events:none;}
  @media(max-width:620px){
    .welcome-banner{min-height:160px;padding:26px 22px;border-radius:16px;}
    .welcome-banner h2{font-size:clamp(30px,7vw,40px);letter-spacing:-.7px;margin-bottom:10px;}
    .welcome-banner p{font-size:15px;max-width:100%;}
  }

</style>
