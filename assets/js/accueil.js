/* Accueil : filtre des filières selon le diplôme. Les animations sont dans app.js et app.css. */
(() => {
	"use strict";
	const $ = (s, r = document) => r.querySelector(s);
	const $$ = (s, r = document) => [...r.querySelectorAll(s)];

	const offre = JSON.parse($("#offre").textContent);
	const cadre = $("[data-trouveur]");
	if (cadre) {
		const choix = $("select", cadre);
		const resultat = $("[data-resultat]", cadre);
		const listes = $("[data-listes]", cadre);
		const onglets = $(".onglets", cadre);
		choix.addEventListener("change", () => {
			const actif = !!choix.value;
			resultat.hidden = !actif;
			listes.hidden = actif;
			onglets.hidden = actif;
			if (!actif) return;
			const par = {};
			(offre[choix.value] || []).forEach((o) => { (par[o.fl] = par[o.fl] || []).push(o.ol); });
			const h = document.createElement("h3");
			h.className = "offre__titre";
			h.textContent = Object.keys(par).length ? "Avec ce diplôme, tu peux présenter" : "Aucune option ouverte pour ce diplôme";
			const ul = document.createElement("ul");
			ul.className = "filieres";
			Object.entries(par).forEach(([f, opts]) => {
				const li = document.createElement("li");
				const b = document.createElement("b"); b.textContent = f;
				const s = document.createElement("span"); s.textContent = opts.join(", ");
				li.append(b, s); ul.append(li);
			});
			resultat.replaceChildren(h, ul);
		});
	}
})();
