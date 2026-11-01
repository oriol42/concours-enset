/*
 * Parcours complet dans Chrome, avec des candidats fictifs :
 *   node tests/parcours.mjs <url> <dossier de sortie> <photo.jpg> <recu.png> <mot de passe admin>
 * Dépendance : puppeteer-core (NODE_PATH) et /usr/bin/google-chrome.
 */
import puppeteer from "puppeteer-core";
import fs from "node:fs";

const [URLB, SORTIE, PHOTO, RECU, MDP] = process.argv.slice(2);
const navigateur = await puppeteer.launch({ executablePath: "/usr/bin/google-chrome", headless: "new", args: ["--no-sandbox"] });
const erreurs = [];
let verifs = 0;
const ok = (c, m) => { if (!c) throw new Error("ECHEC : " + m); verifs++; console.log("ok  " + m); };

async function ouvrir(mobile = false) {
	const ctx = await navigateur.createBrowserContext();
	const p = await ctx.newPage();
	await p.setViewport(mobile ? { width: 390, height: 844, deviceScaleFactor: 2, isMobile: true, hasTouch: true } : { width: 1366, height: 860 });
	p.on("pageerror", (e) => erreurs.push(e.message));
	p.on("console", (m) => { if (m.type() === "error" && !/favicon/.test(m.text())) erreurs.push(m.text()); });
	return p;
}
const aller = (p, c) => p.goto(URLB + c, { waitUntil: "networkidle0" });
const envoyer = async (p) => { await Promise.all([p.waitForNavigation({ waitUntil: "networkidle0" }), p.click("[data-envoi]")]); };
const texte = (p) => p.evaluate(() => document.body.innerText);
const photo = (p, nom) => p.screenshot({ path: `${SORTIE}/${nom}.png`, fullPage: true });
const taper = async (p, id, v) => { await p.$eval("#c-" + id, (e) => { e.value = ""; }); await p.type("#c-" + id, v); };
const date = (p, id, v) => p.$eval("#c-" + id, (e, x) => { e.value = x; e.dispatchEvent(new Event("change", { bubbles: true })); }, v);

async function candidat(p, c) {
	await aller(p, "/candidature/1");
	await p.click(`input[name=cycle][value=${c.cycle}]`).catch(() => p.$eval(`input[name=cycle][value=${c.cycle}]`, (e) => e.click()));
	await p.select("#c-diplome", c.diplome);
	await p.waitForSelector("[data-options] label");
	await p.$eval(`input[name=parcours][value="${c.parcours}"]`, (e) => e.click());
	await p.select("#c-centre", c.centre);
	await envoyer(p);
	await taper(p, "noms", c.noms); await taper(p, "prenoms", c.prenoms);
	await date(p, "date_naissance", c.naissance);
	await taper(p, "lieu_naissance", "Ébolowa");
	await p.$eval("input[name=sexe][value='2']", (e) => e.click());
	await envoyer(p);
	await taper(p, "telephone", c.tel); await taper(p, "email", c.mail); await taper(p, "adresse", "Yaoundé, Mvog-Ada");
	await p.select("#c-region", "18"); await p.select("#c-departement", "Mvila");
	await p.select("#c-arrondissement", "EBOLOWA IER");
	await taper(p, "cni", c.cni); await date(p, "cni_date", "2024-02-10"); await taper(p, "cni_lieu", "Ebolowa");
	await envoyer(p);
}
async function etape45(p, c) {
	await taper(p, "etablissement", "Université de Douala");
	await taper(p, "annee_diplome", "2024");
	await p.select("#c-mention", "AB");
	await (await p.$("#c-photo")).uploadFile(PHOTO);
	await p.waitForFunction(() => /prête/.test(document.querySelector("[data-depot-etat]").textContent));
	await envoyer(p);
	await taper(p, "transaction_num", c.tx);
	await (await p.$("#c-recu")).uploadFile(RECU);
	await p.waitForFunction(() => /prête/.test(document.querySelector("[data-depot-etat]").textContent));
	await envoyer(p);
}

/* Accueil */
for (const [mobile, nom] of [[false, "accueil-pc"], [true, "accueil-mobile"]]) {
	const p = await ouvrir(mobile);
	await aller(p, "/");
	await new Promise((r) => setTimeout(r, 1300));
	await p.screenshot({ path: `${SORTIE}/${nom}-haut.png` });
	await p.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 500) { scrollTo({ top: y, behavior: "instant" }); await new Promise((r) => setTimeout(r, 90)); } scrollTo({ top: 0, behavior: "instant" }); });
	await new Promise((r) => setTimeout(r, 900));
	await photo(p, nom);
	ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth), nom + " sans débordement horizontal");
	await p.select("#trouveur-diplome", "BAC_F3");
	ok(/Électrotechnique/.test(await texte(p)) && /Maintenance et Installation/.test(await p.$eval("[data-resultat]", (e) => e.innerText)), "recherche par diplôme");
	await p.close();
}

/* Candidat A, sur téléphone */
const A = { cycle: "CYC2", diplome: "LIC_TECH", parcours: "BIOMED|MHB", centre: "EBOL", noms: "Ngono Élodie", prenoms: "Marie-Thérèse", naissance: "2000-03-14", tel: "+237 677 12 34 56", mail: "elodie.test@example.invalid", cni: "1234 56789", tx: "SCB20270001" };
const p = await ouvrir(true);
await aller(p, "/candidature/1");
await photo(p, "etape1-vide");
await p.click("[data-envoi]");
ok(/obligatoire/.test(await texte(p)), "erreurs immédiates sur étape vide");
await p.$eval("input[name=cycle][value=CYC2]", (e) => e.click());
await p.select("#c-diplome", "LIC_TECH");
await p.waitForSelector("[data-options] label");
ok((await p.$$("[data-options] label")).length === 3, "trois options pour une Licence de Technologie");
await photo(p, "etape1-mobile");
await candidat(p, A);
ok(p.url().endsWith("/candidature/4/") || p.url().endsWith("/candidature/4"), "étape 4 atteinte");
await photo(p, "etape4-mobile");
await etape45(p, A);
ok(/\/candidature\/6/.test(p.url()), "récapitulatif atteint");
ok(/NGONO ÉLODIE/.test(await texte(p)), "accents conservés dans le nom");
await photo(p, "recap-mobile");
await p.click("[data-envoi]");
await new Promise((r) => setTimeout(r, 300));
ok(/obligatoire/.test(await texte(p)), "déclaration exigée");
await p.$eval("#c-engagement", (e) => e.click());
await envoyer(p);
ok(/mon-dossier/.test(p.url()), "dossier envoyé");
await photo(p, "dossier-mobile");
const numero = (await texte(p)).match(/UEB-\d{4}-\d{6}/)[0];
const pdf = await p.evaluate(async (u) => { const r = await fetch(u); const a = new Uint8Array(await r.arrayBuffer()); return [r.status, r.headers.get("content-type"), [...a].map((b) => String.fromCharCode(b)).join("")]; }, URLB + "/mon-dossier/fiche/");
ok(pdf[0] === 200 && /pdf/.test(pdf[1]), "fiche PDF servie");
fs.writeFileSync(`${SORTIE}/fiche.pdf`, Buffer.from(pdf[2], "latin1"));

const apres = await p.goto(URLB + "/fichier/photo/");
ok(apres.status() === 200, "la photo survit à la génération du PDF");
await aller(p, "/mon-dossier");

/* Reprise */
const r = await ouvrir();
await aller(r, "/reprendre");
await photo(r, "reprendre-pc");
await taper(r, "identifiant", numero); await date(r, "date_naissance", "2001-01-01");
await envoyer(r);
ok(/Aucun dossier/.test(await texte(r)), "reprise refusée avec une mauvaise date");
await taper(r, "identifiant", "677123456"); await date(r, "date_naissance", "2000-03-14");
await envoyer(r);
ok(/mon-dossier/.test(r.url()), "reprise par téléphone");

/* Doublons */
const q = await ouvrir();
await candidat(q, { ...A, noms: "Second", prenoms: "Essai", cni: "123456789", tel: "699000111", mail: "b@example.invalid" });
ok(/déjà avec ce numéro/.test(await texte(q)), "même CNI refusée");
await photo(q, "etape3-erreur-pc");
await taper(q, "cni", "ZZ998877"); await date(q, "cni_date", "2024-02-10"); await envoyer(q);
await etape45(q, { tx: "SCB20270001" });
ok(/déjà utilisé/.test(await texte(q)), "même transaction refusée");
await taper(q, "transaction_num", "SCB20270002");
await (await q.$("#c-recu")).uploadFile(RECU);
await q.waitForFunction(() => /prête/.test(document.querySelector("[data-depot-etat]").textContent));
await envoyer(q);
await photo(q, "recap-pc");
await q.$eval("#c-engagement", (e) => e.click());
await envoyer(q);

/* Accès protégés */
const anonyme = await ouvrir();
const bloque = await anonyme.goto(URLB + "/fichier/photo/");
ok(bloque.status() === 404, "photo inaccessible sans session");
erreurs.length = 0; /* Ce 404 est attendu. */
await aller(anonyme, "/gestion/");
ok(/wp-login/.test(anonyme.url()), "gestion réservée");
const dir = await anonyme.goto(URLB + "/wp-content/uploads/ceb-concours/");
ok(dir.status() !== 200 || !/\.jpg/.test(await anonyme.content()), "dossier des fichiers non listé");

/* Scolarité */
await anonyme.goto(URLB + "/wp-login.php", { waitUntil: "networkidle0" });
await anonyme.type("#user_login", "admin"); await anonyme.type("#user_pass", MDP);
await Promise.all([anonyme.waitForNavigation(), anonyme.click("#wp-submit")]);
const g = anonyme;
await aller(g, "/gestion/?vue=bord");
ok(/Candidatures reçues/.test(await texte(g)), "tableau de bord");
await photo(g, "gestion-bord");
await aller(g, "/gestion/?vue=dossiers&q=NGONO");
await photo(g, "gestion-liste");
await Promise.all([g.waitForNavigation(), g.click("td a")]);
await photo(g, "gestion-dossier");
const decider = async (d, motif) => {
	const f = await g.$(`form.decision:has(input[value=${d}])`);
	if (motif) {
		await f.evaluate((x) => { x.closest("details").open = true; });
		await (await f.$("input[name=motif]")).type(motif);
	}
	await Promise.all([g.waitForNavigation(), f.$eval("button", (b) => b.click())]);
};
await decider("valider");
ok(/Vérifie d'abord le paiement/.test(await texte(g)), "validation impossible sans paiement vérifié");
await decider("rejeter", "Reçu illisible");
await p.reload({ waitUntil: "networkidle0" });
ok(/Reçu illisible/.test(await texte(p)), "le candidat voit le motif");
await aller(p, "/candidature/6");
await p.$eval("#c-engagement", (e) => e.click());
await envoyer(p);
await aller(g, "/gestion/?vue=dossiers&q=NGONO");
await Promise.all([g.waitForNavigation(), g.click("td a")]);
await decider("verifier_paiement");
await decider("valider");
ok(/Toutes les pièces doivent/.test(await texte(g)), "validation impossible sans les pièces");
await g.$$eval("input[name='pieces[]']", (c) => c.forEach((x) => { x.checked = true; }));
await Promise.all([g.waitForNavigation(), g.$eval("form.pointage button", (b) => b.click())]);
await decider("valider");
ok(/Dossier validé/.test(await g.$eval("#t-journal", (e) => e.textContent)), "journal des décisions");
await aller(p, "/mon-dossier/notifications");
ok(/Paiement vérifié/.test(await texte(p)) && /Dossier à corriger/.test(await texte(p)) && /Dossier validé/.test(await texte(p)), "le candidat reçoit ses notifications");
await photo(p, "notifs-candidat");
await aller(g, "/gestion/?vue=notifications");
ok(/Nouvelle candidature/.test(await texte(g)) && /Dossier corrigé/.test(await texte(g)), "l'équipe reçoit ses notifications");

/* Droits : un agent vérifie et pointe, sans valider ni régler. */
await aller(g, "/gestion/?vue=equipe");
await photo(g, "gestion-equipe");
await taper(g, "nom", "Agent Essai"); await taper(g, "email", "agent@example.invalid"); await g.select("#c-role", "ceb_scolarite");
await Promise.all([g.waitForNavigation(), g.$eval("form.formulaire button", (b) => b.click())]);
const mdpAgent = (await texte(g)).match(/provisoire (\S+)\./)[1];
const ag = await ouvrir();
await aller(ag, "/wp-login.php");
await ag.type("#user_login", "agent@example.invalid"); await ag.type("#user_pass", mdpAgent);
await Promise.all([ag.waitForNavigation(), ag.click("#wp-submit")]);
await aller(ag, "/gestion/");
ok(!/Réglages/.test(await texte(ag)) && !/Équipe/.test(await texte(ag)), "menu limité pour un agent");
ok((await ag.goto(URLB + "/gestion/?vue=reglages")).status() === 403, "réglages refusés à un agent");
await aller(ag, "/gestion/?vue=dossiers&file=paiements");
await Promise.all([ag.waitForNavigation(), ag.click("td a")]);
await ag.$eval("form.decision input[value=verifier_paiement]", (x) => x.form.querySelector("button").click());
await ag.waitForNavigation();
ok(/Paiement vérifié/.test(await texte(ag)), "un agent vérifie un paiement");
await aller(g, "/gestion/?vue=equipe");
await Promise.all([g.waitForNavigation(), g.$eval(".membres select", (s) => { s.value = "ceb_direction"; s.form.submit(); })]);
ok((await ag.goto(URLB + "/gestion/?vue=export-csv")).status() === 403, "export refusé à la direction");
await aller(ag, "/gestion/?vue=dossiers");
await Promise.all([ag.waitForNavigation(), ag.click("td a")]);
ok(!(await ag.$("form.decision")), "la direction ne décide pas");
await aller(g, "/gestion/?vue=equipe");
await Promise.all([g.waitForNavigation(), g.$eval(".membres button", (b) => { window.confirm = () => true; b.click(); })]);
ok((await ag.goto(URLB + "/gestion/")).status() === 403, "accès retiré effectif");
erreurs.length = 0;
await aller(p, "/mon-dossier");
ok(/Tu es admis à composer/.test(await texte(p)), "dossier validé côté candidat");
await aller(p, "/candidature/3");
ok(/mon-dossier/.test(p.url()), "dossier validé non modifiable");

const csv = await g.evaluate(async (u) => (await fetch(u)).text(), URLB + "/gestion/?vue=export-csv");
ok(/NGONO ÉLODIE/.test(csv) && csv.split("\n").length >= 3, "export CSV");
await aller(g, "/gestion/?vue=reglages");
await photo(g, "gestion-reglages");

/* Clôture : réglage appliqué partout, candidature fermée, dossier toujours lisible. */
await date(g, "cloture", "2020-01-01");
await g.$eval("input[name='centres[]'][value=GAR]", (e) => e.click());
await Promise.all([g.waitForNavigation(), g.click(".reglages button[type=submit]")]);
const ferme = await ouvrir();
await aller(ferme, "/");
ok(/Inscriptions closes/.test(await texte(ferme)), "accueil en session close");
await aller(ferme, "/candidature/1");
ok(!/candidature/.test(ferme.url()), "candidature fermée après clôture");
await aller(p, "/mon-dossier");
ok(/Ma fiche PDF/.test(await texte(p)), "fiche encore disponible après clôture");
await aller(g, "/gestion/?vue=reglages");
await date(g, "cloture", "");
await g.$eval("input[name='centres[]'][value=GAR]", (e) => e.click());
await Promise.all([g.waitForNavigation(), g.click(".reglages button[type=submit]")]);
await aller(ferme, "/candidature/1");
ok((await ferme.$$eval("#c-centre option", (o) => o.map((x) => x.value))).includes("GAR"), "centre rouvert");

/* QR */
const jeton = pdf[2].length; ok(jeton > 1000, "PDF non vide");
console.log(`\n${verifs} vérifications, ${erreurs.length} erreur(s) navigateur`);
erreurs.forEach((e) => console.log("  " + e));
await navigateur.close();
process.exit(erreurs.length ? 1 : 0);
