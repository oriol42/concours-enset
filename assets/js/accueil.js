/*
 * Accueil : filtre des filières par diplôme et mouvement. Chaque animation
 * guide la lecture : entrée du message, frise qui suit la procédure, chiffres.
 * Sans JS ou avec « réduire les animations », la page reste complète.
 */
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

	if (!window.gsap || matchMedia("(prefers-reduced-motion: reduce)").matches) {
		const f = $("[data-frise]"); if (f) f.style.setProperty("--avance", 1);
		return;
	}
	gsap.registerPlugin(ScrollTrigger);
	const sortie = "expo.out";

	gsap.from("[data-entree]", { y: 24, autoAlpha: 0, duration: .9, ease: sortie, stagger: .08 });
	gsap.from("[data-scene]", { y: 50, autoAlpha: 0, duration: 1.2, ease: sortie, delay: .1 });

	$$("[data-apparition]").forEach((el) => gsap.from(el, {
		y: 18, autoAlpha: 0, duration: .55, ease: sortie, scrollTrigger: { trigger: el, start: "top bottom+=80", once: true },
	}));

	$$("[data-compte]").forEach((el) => {
		const cible = { n: 0 };
		gsap.to(cible, {
			n: +el.dataset.compte, duration: 1, ease: "power2.out", snap: { n: 1 },
			onUpdate: () => { el.textContent = cible.n; },
			scrollTrigger: { trigger: el, start: "top 96%", once: true },
		});
	});

	const frise = $("[data-frise]");
	if (frise) {
		gsap.to(frise, { "--avance": 1, ease: "none", scrollTrigger: { trigger: frise, start: "top 78%", end: "bottom 60%", scrub: .4 } });
		gsap.from($$(".frise__etape", frise), {
			y: 20, autoAlpha: 0, duration: .7, ease: sortie, stagger: .1, scrollTrigger: { trigger: frise, start: "top bottom", once: true },
		});
	}
})();
