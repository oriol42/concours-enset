/* Partagé par toutes les pages : copie du numéro de compte, envoi unique, onglets. */
(() => {
	"use strict";
	const $$ = (s, r = document) => [...r.querySelectorAll(s)];

	$$("[data-copier]").forEach((b) => b.addEventListener("click", async () => {
		const etat = b.querySelector("[data-copie-etat]");
		try {
			await navigator.clipboard.writeText(b.dataset.copier);
			etat.textContent = "Copié";
			setTimeout(() => { etat.textContent = "Copier"; }, 2000);
		} catch {
			etat.textContent = "Sélectionne le numéro";
		}
	}));

	$$('[role="tablist"]').forEach((liste) => {
		const onglets = $$('[role="tab"]', liste);
		onglets.forEach((o, i) => {
			const activer = () => onglets.forEach((x) => {
				const actif = x === o;
				x.setAttribute("aria-selected", actif);
				document.getElementById(x.getAttribute("aria-controls")).hidden = !actif;
			});
			o.addEventListener("click", activer);
			o.addEventListener("keydown", (e) => {
				if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
				const s = onglets[(i + (e.key === "ArrowRight" ? 1 : onglets.length - 1)) % onglets.length];
				s.focus(); s.click();
			});
		});
	});

	/* Trois messages d'erreur plutôt qu'un seul à la fois, et jamais deux envois. */
	$$("[data-etape-form]").forEach((form) => {
		form.addEventListener("submit", (e) => {
			if (window.cebValider && !window.cebValider(form)) { e.preventDefault(); return; }
			const b = form.querySelector("[data-envoi]");
			if (b) {
				b.disabled = true;
				const t = b.querySelector("span");
				if (t) t.textContent = "Enregistrement…";
			}
		});
	});
	/* Retour arrière du navigateur : le bouton doit redevenir actif. */
	addEventListener("pageshow", () => $$("[data-envoi]").forEach((b) => { b.disabled = false; }));

	/* Dates : les barres s'ajoutent seules, le clavier numérique suffit. */
	$$("[data-date]").forEach((c) => c.addEventListener("input", (e) => {
		if (e.inputType === "deleteContentBackward") return;
		const d = c.value.replace(/\D/g, "").slice(0, 8);
		c.value = d.length > 4 ? d.slice(0, 2) + "/" + d.slice(2, 4) + "/" + d.slice(4) : d.length > 2 ? d.slice(0, 2) + "/" + d.slice(2) : d;
	}));

	/* Cloche : compteur actualisé toutes les 30 secondes, avec une annonce à l'arrivée. */
	const cloche = document.querySelector("[data-cloche]");
	if (cloche) {
		const pastille = document.querySelector("[data-cloche-nombre]");
		const annonce = (texte) => {
			const t = document.createElement("div");
			t.className = "annonce"; t.setAttribute("role", "status"); t.textContent = texte;
			document.body.append(t);
			setTimeout(() => t.remove(), 6000);
		};
		let dernier = +pastille.textContent || 0;
		setInterval(async () => {
			if (document.hidden) return;
			try {
				const r = await (await fetch(cloche.dataset.cloche, { credentials: "same-origin" })).json();
				if (r.n > dernier) annonce(r.titre || "Nouvelle notification");
				dernier = r.n;
				pastille.textContent = r.n;
				pastille.hidden = !r.n;
			} catch { /* Réseau coupé : nouvel essai au prochain passage. */ }
		}, 30000);
	}

	document.querySelector("[data-resume]")?.focus();
	document.querySelector("[data-ouvrir]")?.focus();
})();
