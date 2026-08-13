/* ==========================================================
   NisaaAmal — IndexedDB data layer
   Object stores: news, programs, messages, settings, stats, tasks, meta
   ========================================================== */
(function(global){
  const DB_NAME = 'nisaaAmalDB';
  const DB_VERSION = 2;
  const LS_LEGACY = 'nisaaAmal_data_v1';
  const LS_PW = 'nisaaAmal_pw_v1';

  const STORES = {
    news:     { keyPath:'id', autoIncrement:true, indexes:[
      { name:'date', keyPath:'date' },
      { name:'category', keyPath:'category' },
      { name:'published', keyPath:'published' }
    ]},
    programs: { keyPath:'id', autoIncrement:true, indexes:[
      { name:'order', keyPath:'order' }
    ]},
    messages: { keyPath:'id', autoIncrement:true, indexes:[
      { name:'date', keyPath:'date' },
      { name:'read', keyPath:'read' }
    ]},
    stats:    { keyPath:'id', autoIncrement:true, indexes:[
      { name:'order', keyPath:'order' }
    ]},
    tasks:    { keyPath:'id', autoIncrement:true, indexes:[
      { name:'status', keyPath:'status' },
      { name:'department', keyPath:'department' },
      { name:'createdAt', keyPath:'createdAt' }
    ]},
    settings: { keyPath:'key' },
    meta:     { keyPath:'key' }
  };

  const DEFAULT_ORG = {
    name:'جمعية نساء الأمل',
    tagline:'دعم النساء والأطفال في مناطق النزاع',
    address:'عمّان — الأردن، شارع المدينة المنورة',
    phone:'+962 79 000 0000',
    email:'info@nisaa-amal.org',
    hours:'الأحد - الخميس: 9:00 ص - 5:00 م',
    years:'12+',
    heroTitleA:'معاً من أجل',
    heroTitleHL:'نساء وأطفال',
    heroTitleB:'يستحقون الأمان والكرامة',
    heroDesc:'جمعية خيرية نسوية تكرّس جهودها لدعم النساء والأطفال المتضررين من النزاعات في مختلف أنحاء العالم، عبر برامج إغاثة وتعليم وتمكين تعيد لهم الأمل وتبني مستقبلاً أفضل.',
    aboutTitle:'نسعى لعالم تعيش فيه كل امرأة وطفل بكرامة وأمان',
    aboutP1:'جمعية نساء الأمل منظمة خيرية مستقلة، تأسست بسواعد نسائية مؤمنة بأن العمل الإنساني هو الجسر الذي يعبر بنا نحو غدٍ أفضل. نعمل في مناطق النزاع لتقديم الدعم الفوري والمستدام للأكثر هشاشة.',
    aboutP2:'نلتزم بالشفافية والمحاسبة، ونعمل بالشراكة مع المجتمعات المحلية لضمان وصول الدعم إلى مستحقيه، مع احترام كامل لكرامتهم وثقافتهم.',
    vision:'عالم تعيش فيه كل امرأة وكل طفل بكرامة، بعيداً عن الخوف والعنف، يتمتعون بحقهم في التعليم والصحة والأمان، ويُسهمون في بناء مجتمعاتهم بثقة.',
    mission:'نعمل على تمكين النساء وحماية الأطفال في مناطق النزاع من خلال برامج إغاثية وتنموية مستدامة، نُقدّمها بشراكة حقيقية مع المجتمعات المحلية، مرتكزين على قيم الكرامة والعدالة والشفافية.',
    footerDesc:'جمعية خيرية نسوية مستقلة، نعمل من أجل دعم النساء والأطفال في مناطق النزاع وإعادة الأمل لحياتهم.'
  };

  const DEFAULT_STATS = [
    { order:1, icon:'women', num:'15,000+', label:'امرأة مستفيدة' },
    { order:2, icon:'child', num:'28,000+', label:'طفلة وطفل تم دعمهم' },
    { order:3, icon:'hand',  num:'12',      label:'منطقة عمل ميدانية' },
    { order:4, icon:'heart', num:'350+',    label:'متطوعة ومتطوع' }
  ];

  const DEFAULT_PROGRAMS = [
    { order:1, icon:'aid',     title:'الإغاثة العاجلة', desc:'توفير الغذاء والمأوى والمستلزمات الأساسية للعائلات المتضررة في مناطق النزاع، مع التركيز على الفئات الأكثر هشاشة.' },
    { order:2, icon:'edu',     title:'تعليم الأطفال',  desc:'إنشاء مساحات تعليمية آمنة في المخيمات ومناطق الطوارئ، وتوفير المعلمات والمستلزمات الدراسية للأطفال.' },
    { order:3, icon:'health',  title:'الرعاية الصحية', desc:'توفير الرعاية الطبية للأمهات والأطفال، وحملات التوعية الصحية والدعم النفسي للنساء المتضررات.' },
    { order:4, icon:'empower', title:'تمكين المرأة',   desc:'برامج تدريب مهني وريادة أعمال للنساء، تمكّنهن من إعالة أسرهن واستعادة استقلاليتهن الاقتصادية.' },
    { order:5, icon:'shield',  title:'الحماية والدعم النفسي', desc:'تقديم المساندة النفسية والقانونية للنساء الناجيات من العنف، وتوفير ملاجئ آمنة عند الحاجة.' },
    { order:6, icon:'voice',   title:'المناصرة والتوعية', desc:'الدفاع عن قضايا النساء والأطفال في مناطق النزاع عبر التقارير والحملات الإعلامية الدولية.' }
  ];

  const DEFAULT_NEWS = [
    {
      title:'افتتاح مركز جديد لدعم الأمهات في المخيم الشمالي',
      category:'أنشطة',
      date:'2026-04-28',
      image:'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=1200&q=80',
      excerpt:'بدعم من شركائنا، افتتحنا هذا الأسبوع مركزاً متكاملاً يقدم خدمات الرعاية للأمهات الحوامل والمرضعات في المخيم الشمالي.',
      content:'في خطوة نوعية لتعزيز الدعم المقدم للأمهات في مناطق النزاع، تم افتتاح مركز جديد لدعم الأمهات في المخيم الشمالي بحضور شركاء الجمعية والشخصيات المحلية.\n\nيقدم المركز خدمات متكاملة تشمل:\n• الرعاية الصحية للأمهات الحوامل والمرضعات\n• استشارات التغذية للأطفال دون سن الخامسة\n• ورش تدريبية حول رعاية الرضع\n• الدعم النفسي والاجتماعي\n\nمن المتوقع أن يخدم المركز أكثر من 1500 أم وطفل خلال الأشهر الستة الأولى من تشغيله. يأتي هذا الافتتاح ضمن خطة الجمعية الاستراتيجية لتوسيع نطاق خدماتها في المناطق الأكثر احتياجاً.',
      author:'فريق التحرير',
      published:1
    },
    {
      title:'حملة "كتاب لكل طفلة" توزع 5000 حقيبة مدرسية',
      category:'أخبار',
      date:'2026-04-15',
      image:'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1200&q=80',
      excerpt:'انطلقت حملتنا السنوية "كتاب لكل طفلة" بتوزيع 5000 حقيبة مدرسية مجهزة بالكامل على الطالبات في المناطق المتضررة.',
      content:'في إطار التزامها بضمان حق التعليم لكل طفلة، أطلقت جمعية نساء الأمل النسخة الخامسة من حملتها السنوية "كتاب لكل طفلة"، والتي تستهدف هذا العام توزيع 5000 حقيبة مدرسية مجهزة بالكامل.\n\nتتضمن كل حقيبة:\n• الكتب والدفاتر اللازمة للعام الدراسي\n• أدوات قرطاسية متكاملة\n• زي مدرسي\n• وجبة خفيفة\n\nشاركت في الحملة فرق من المتطوعات في 8 مناطق مختلفة، وامتدت على مدى أسبوعين كاملين. عبّرت الأمهات عن امتنانهن العميق لهذه المبادرة التي خففت عنهن عبء بداية العام الدراسي.',
      author:'فريق التحرير',
      published:1
    },
    {
      title:'انطلاق برنامج تدريب مهني لـ 200 امرأة',
      category:'برامج',
      date:'2026-03-22',
      image:'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=1200&q=80',
      excerpt:'بدأنا برنامجاً تدريبياً لمدة ستة أشهر يستهدف 200 امرأة من المعيلات لأسرهن في مجالات الخياطة والطبخ والحرف اليدوية.',
      content:'انطلق هذا الأسبوع برنامج تدريبي شامل يستهدف 200 امرأة من المعيلات لأسرهن، يهدف إلى تمكينهن اقتصادياً عبر اكتساب مهارات مهنية تساعدهن في إيجاد مصدر دخل مستدام.\n\nيتضمن البرنامج التدريب في مجالات:\n• الخياطة والتفصيل (60 امرأة)\n• الطبخ والتغذية (50 امرأة)\n• الحرف اليدوية والإكسسوارات (50 امرأة)\n• الحلاقة والتجميل (40 امرأة)\n\nيمتد البرنامج لستة أشهر، تتلقى خلالها المشاركات تدريباً نظرياً وعملياً، بالإضافة إلى محاضرات في إدارة المشاريع الصغيرة. وفي ختام البرنامج، ستحصل كل مشاركة على شهادة معتمدة وحقيبة أدوات لبدء مشروعها الخاص.',
      author:'فريق التحرير',
      published:1
    }
  ];

  let dbPromise = null;

  function open(){
    if(dbPromise) return dbPromise;
    dbPromise = new Promise((resolve, reject) => {
      if(!('indexedDB' in window)){ reject(new Error('IndexedDB not supported')); return; }
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = (e) => {
        const db = req.result;
        for(const [name, cfg] of Object.entries(STORES)){
          if(!db.objectStoreNames.contains(name)){
            const store = db.createObjectStore(name, { keyPath:cfg.keyPath, autoIncrement:!!cfg.autoIncrement });
            (cfg.indexes||[]).forEach(idx => store.createIndex(idx.name, idx.keyPath, idx.options||{}));
          }
        }
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    }).then(async (db) => {
      await seedIfEmpty(db);
      await migrateLegacyIfAny(db);
      return db;
    });
    return dbPromise;
  }

  function tx(db, names, mode){
    const t = db.transaction(names, mode || 'readonly');
    return Array.isArray(names) ? names.map(n => t.objectStore(n)) : t.objectStore(names);
  }

  function reqP(req){
    return new Promise((resolve, reject) => {
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }

  async function seedIfEmpty(db){
    const settingsStore = tx(db, 'settings', 'readwrite');
    const orgRow = await reqP(settingsStore.get('org'));
    if(!orgRow){
      await reqP(settingsStore.put({ key:'org', value: { ...DEFAULT_ORG } }));

      const newsStore = tx(db, 'news', 'readwrite');
      for(const n of DEFAULT_NEWS) await reqP(newsStore.add({ ...n, createdAt: Date.now() }));

      const progStore = tx(db, 'programs', 'readwrite');
      for(const p of DEFAULT_PROGRAMS) await reqP(progStore.add({ ...p }));

      const statsStore = tx(db, 'stats', 'readwrite');
      for(const s of DEFAULT_STATS) await reqP(statsStore.add({ ...s }));
    }
  }

  async function migrateLegacyIfAny(db){
    let legacy;
    try{ const raw = localStorage.getItem(LS_LEGACY); if(raw) legacy = JSON.parse(raw); }catch{ return; }
    if(!legacy) return;

    const meta = tx(db, 'meta', 'readwrite');
    const migrated = await reqP(meta.get('legacyMigrated'));
    if(migrated) return;

    if(legacy.org){
      const settingsStore = tx(db, 'settings', 'readwrite');
      await reqP(settingsStore.put({ key:'org', value: { ...DEFAULT_ORG, ...legacy.org } }));
    }
    if(Array.isArray(legacy.news) && legacy.news.length){
      const newsStore = tx(db, 'news', 'readwrite');
      await reqP(newsStore.clear());
      for(const n of legacy.news){
        const { id, ...rest } = n;
        await reqP(newsStore.add({ ...rest, published:1, createdAt: Date.now() }));
      }
    }
    if(Array.isArray(legacy.programs) && legacy.programs.length){
      const progStore = tx(db, 'programs', 'readwrite');
      await reqP(progStore.clear());
      let i = 1;
      for(const p of legacy.programs) await reqP(progStore.add({ order:i++, ...p }));
    }
    if(Array.isArray(legacy.stats) && legacy.stats.length){
      const statsStore = tx(db, 'stats', 'readwrite');
      await reqP(statsStore.clear());
      let i = 1;
      for(const s of legacy.stats) await reqP(statsStore.add({ order:i++, ...s }));
    }
    if(Array.isArray(legacy.messages) && legacy.messages.length){
      const msgStore = tx(db, 'messages', 'readwrite');
      for(const m of legacy.messages){
        const { id, ...rest } = m;
        await reqP(msgStore.add({ ...rest, read: rest.read ? 1 : 0 }));
      }
    }
    await reqP(meta.put({ key:'legacyMigrated', value:true, at:Date.now() }));
    try{ localStorage.removeItem(LS_LEGACY); }catch{}
  }

  /* ===== Public API ===== */
  const DB = {
    async ready(){ return open(); },

    async getOrg(){
      const db = await open();
      const r = await reqP(tx(db,'settings').get('org'));
      return r ? { ...DEFAULT_ORG, ...r.value } : { ...DEFAULT_ORG };
    },
    async setOrg(org){
      const db = await open();
      const cur = await reqP(tx(db,'settings').get('org'));
      const merged = { ...(cur ? cur.value : DEFAULT_ORG), ...org };
      await reqP(tx(db,'settings','readwrite').put({ key:'org', value:merged }));
      return merged;
    },

    async listNews({ publishedOnly=false } = {}){
      const db = await open();
      const all = await reqP(tx(db,'news').getAll());
      const items = publishedOnly ? all.filter(n => n.published !== 0) : all;
      return items.sort((a,b) => (b.date||'').localeCompare(a.date||''));
    },
    async getNews(id){
      const db = await open();
      return await reqP(tx(db,'news').get(Number(id)));
    },
    async saveNews(item){
      const db = await open();
      const store = tx(db,'news','readwrite');
      const now = Date.now();
      const payload = { ...item, updatedAt: now, published: item.published ?? 1 };
      if(!item.id) payload.createdAt = now;
      const id = await reqP(store.put(payload));
      return id;
    },
    async deleteNews(id){
      const db = await open();
      await reqP(tx(db,'news','readwrite').delete(Number(id)));
    },
    async getRelatedNews(id, limit=3){
      const db = await open();
      const all = await reqP(tx(db,'news').getAll());
      const target = all.find(n => n.id === Number(id));
      if(!target) return [];
      const others = all.filter(n => n.id !== Number(id) && n.published !== 0);
      const sameCategory = others.filter(n => n.category === target.category);
      const rest = others.filter(n => n.category !== target.category);
      return [...sameCategory, ...rest]
        .sort((a,b) => (b.date||'').localeCompare(a.date||''))
        .slice(0, limit);
    },

    async listPrograms(){
      const db = await open();
      const all = await reqP(tx(db,'programs').getAll());
      return all.sort((a,b) => (a.order||0) - (b.order||0));
    },
    async saveProgram(item){
      const db = await open();
      const id = await reqP(tx(db,'programs','readwrite').put(item));
      return id;
    },
    async deleteProgram(id){
      const db = await open();
      await reqP(tx(db,'programs','readwrite').delete(Number(id)));
    },

    async listStats(){
      const db = await open();
      const all = await reqP(tx(db,'stats').getAll());
      return all.sort((a,b) => (a.order||0) - (b.order||0));
    },
    async replaceStats(items){
      const db = await open();
      const store = tx(db,'stats','readwrite');
      await reqP(store.clear());
      let i = 1;
      for(const s of items) await reqP(store.add({ order:i++, ...s }));
    },

    async listMessages(){
      const db = await open();
      const all = await reqP(tx(db,'messages').getAll());
      return all.sort((a,b) => (b.id||0) - (a.id||0));
    },
    async addMessage(msg){
      const db = await open();
      return await reqP(tx(db,'messages','readwrite').add({ ...msg, read:0, date: msg.date || new Date().toISOString() }));
    },
    async markMessage(id, read){
      const db = await open();
      const store = tx(db,'messages','readwrite');
      const m = await reqP(store.get(Number(id)));
      if(m){ m.read = read ? 1 : 0; await reqP(store.put(m)); }
    },
    async markAllRead(){
      const db = await open();
      const store = tx(db,'messages','readwrite');
      const all = await reqP(store.getAll());
      for(const m of all){ if(!m.read){ m.read = 1; await reqP(store.put(m)); } }
    },
    async deleteMessage(id){
      const db = await open();
      await reqP(tx(db,'messages','readwrite').delete(Number(id)));
    },
    async unreadCount(){
      const db = await open();
      const all = await reqP(tx(db,'messages').getAll());
      return all.filter(m => !m.read).length;
    },

    /* ===== Tasks (نظام المهام: مدير ⇄ مدير قسم) ===== */
    async listTasks({ status=null, department=null } = {}){
      const db = await open();
      let all = await reqP(tx(db,'tasks').getAll());
      if(status) all = all.filter(t => t.status === status);
      if(department) all = all.filter(t => t.department === department);
      return all.sort((a,b) => (b.createdAt||0) - (a.createdAt||0));
    },
    async getTask(id){
      const db = await open();
      return await reqP(tx(db,'tasks').get(Number(id)));
    },
    async saveTask(item){
      const db = await open();
      const store = tx(db,'tasks','readwrite');
      const now = Date.now();
      let payload;
      if(item.id){
        const existing = await reqP(store.get(Number(item.id)));
        payload = { ...(existing||{}), ...item, updatedAt: now };
      }else{
        payload = {
          title:'', details:'', department:'', priority:'متوسطة',
          dueDate:'', assignedBy:'المدير',
          status:'جديدة',            // جديدة | قيد التنفيذ | تمت | لم تتم
          managerNote:'', reply:'', repliedAt:null, repliedBy:'',
          ...item,
          createdAt: now, updatedAt: now
        };
      }
      const id = await reqP(store.put(payload));
      return id;
    },
    async replyTask(id, { status, reply, repliedBy='مدير القسم' }){
      const db = await open();
      const store = tx(db,'tasks','readwrite');
      const t = await reqP(store.get(Number(id)));
      if(!t) return null;
      t.status = status || t.status;
      t.reply = reply != null ? reply : t.reply;
      t.repliedBy = repliedBy;
      t.repliedAt = Date.now();
      t.updatedAt = Date.now();
      await reqP(store.put(t));
      return t;
    },
    async deleteTask(id){
      const db = await open();
      await reqP(tx(db,'tasks','readwrite').delete(Number(id)));
    },
    async taskCounts(){
      const db = await open();
      const all = await reqP(tx(db,'tasks').getAll());
      const c = { total:all.length, 'جديدة':0, 'قيد التنفيذ':0, 'تمت':0, 'لم تتم':0 };
      all.forEach(t => { if(c[t.status] != null) c[t.status]++; });
      return c;
    },

    /* Auth */
    getPassword(){ return localStorage.getItem(LS_PW) || 'admin123'; },
    setPassword(pw){ localStorage.setItem(LS_PW, pw); },

    /* Backup/Restore */
    async exportAll(){
      const db = await open();
      const [news, programs, stats, messages, tasks, settings, meta] = await Promise.all([
        reqP(tx(db,'news').getAll()),
        reqP(tx(db,'programs').getAll()),
        reqP(tx(db,'stats').getAll()),
        reqP(tx(db,'messages').getAll()),
        reqP(tx(db,'tasks').getAll()),
        reqP(tx(db,'settings').getAll()),
        reqP(tx(db,'meta').getAll())
      ]);
      return { schema:'nisaaAmal/v1', exportedAt:new Date().toISOString(), news, programs, stats, messages, tasks, settings, meta };
    },
    async importAll(payload){
      const db = await open();
      const t = db.transaction(['news','programs','stats','messages','tasks','settings','meta'], 'readwrite');
      const get = (n) => t.objectStore(n);
      await Promise.all(['news','programs','stats','messages','tasks','settings','meta'].map(n => reqP(get(n).clear())));
      const put = (s, items) => Promise.all((items||[]).map(it => reqP(s.add(it))));
      await put(get('news'), payload.news);
      await put(get('programs'), payload.programs);
      await put(get('stats'), payload.stats);
      await put(get('messages'), payload.messages);
      await put(get('tasks'), payload.tasks);
      await Promise.all((payload.settings||[]).map(it => reqP(get('settings').put(it))));
      await Promise.all((payload.meta||[]).map(it => reqP(get('meta').put(it))));
      return new Promise((resolve, reject) => { t.oncomplete = () => resolve(); t.onerror = () => reject(t.error); });
    },
    async resetAll(){
      const db = await open();
      const t = db.transaction(['news','programs','stats','messages','tasks','settings','meta'], 'readwrite');
      await Promise.all(['news','programs','stats','messages','tasks','settings','meta'].map(n => reqP(t.objectStore(n).clear())));
      await new Promise((res, rej) => { t.oncomplete = res; t.onerror = () => rej(t.error); });
      await seedIfEmpty(db);
    }
  };

  /* ===== Shared icons ===== */
  DB.ICONS = {
    women:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M5 22l2-7h10l2 7"/><path d="M9 11l-2 4M15 11l2 4"/></svg>`,
    child:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="6" r="3"/><path d="M9 14v8M15 14v8M7 11h10l-2 6H9l-2-6z"/></svg>`,
    hand: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6m-3-3h6"/><path d="M3 12c0-2 1-4 3-4 3 0 4 3 6 3s3-3 6-3c2 0 3 2 3 4 0 5-5 10-9 10s-9-5-9-10z"/></svg>`,
    heart:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>`,
    aid:  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L4 7v6c0 5 3.5 9 8 10 4.5-1 8-5 8-10V7l-8-5z"/><path d="M12 8v6M9 11h6"/></svg>`,
    edu:  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10L12 4 2 10l10 6 10-6z"/><path d="M6 12v5c0 1 3 3 6 3s6-2 6-3v-5"/></svg>`,
    health:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>`,
    empower:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 6 6 1-4.5 4.5L18 20l-6-3-6 3 1.5-6.5L3 9l6-1 3-6z"/></svg>`,
    shield:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l9 4v6c0 5-4 9-9 10-5-1-9-5-9-10V6l9-4z"/></svg>`,
    voice:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-7v16l-9-7zM21 7v10M17 9v6"/></svg>`,
    star: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-7z"/></svg>`,
    book: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>`,
    globe:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 010 20M12 2a15 15 0 000 20"/></svg>`
  };

  DB.ICON_LABELS = {
    women:'امرأة', child:'طفل', hand:'يد', heart:'قلب',
    aid:'إغاثة', edu:'تعليم', health:'صحة', empower:'تمكين',
    shield:'حماية', voice:'مناصرة', star:'نجمة', book:'كتاب', globe:'عالم'
  };

  /* ===== Shared helpers ===== */
  DB.escapeHtml = (s) => String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
  DB.escapeAttr = (s) => DB.escapeHtml(s).replace(/`/g,'&#96;');
  DB.formatDate = (d) => {
    if(!d) return '';
    try{
      const dt = new Date(d);
      const months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
      return `${dt.getDate()} ${months[dt.getMonth()]} ${dt.getFullYear()}`;
    }catch{ return d; }
  };
  DB.formatDateTime = (d) => {
    if(!d) return '—';
    try{
      const dt = new Date(d);
      const day = String(dt.getDate()).padStart(2,'0');
      const mo = String(dt.getMonth()+1).padStart(2,'0');
      const h = String(dt.getHours()).padStart(2,'0');
      const mi = String(dt.getMinutes()).padStart(2,'0');
      return `${dt.getFullYear()}-${mo}-${day} ${h}:${mi}`;
    }catch{ return d; }
  };

  global.NisaaDB = DB;
})(window);
