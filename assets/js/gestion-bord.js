/* Tableau de bord : graphiques Chart.js, comme le back-office de préinscription. */
(() => {
	"use strict";
	const source = document.getElementById("bord-donnees");
	if (!source || !window.Chart) return;
	const D = JSON.parse(source.textContent);
	const VERT = "#007a3d", TILLEUL = "#8cc63f", NUIT = "#0c2a1b", ENCRE = "#141f19", GRIS = "#4b5a51", FILET = "#e6ece6";
	const calme = matchMedia("(prefers-reduced-motion: reduce)").matches;

	Chart.defaults.font.family = '"Source Sans 3", "Segoe UI", sans-serif';
	Chart.defaults.font.size = 13;
	Chart.defaults.color = GRIS;
	Chart.defaults.animation = calme ? false : { duration: 700, easing: "easeOutQuart" };
	Object.assign(Chart.defaults.plugins.tooltip, { backgroundColor: NUIT, padding: 10, cornerRadius: 10, displayColors: false, titleFont: { weight: "600" } });
	Chart.defaults.plugins.legend.display = false;

	const fr = new Intl.DateTimeFormat("fr-FR", { day: "2-digit", month: "2-digit" });
	const long = new Intl.DateTimeFormat("fr-FR", { weekday: "long", day: "numeric", month: "long" });
	const date = (d) => new Date(d + "T12:00:00");
	const accord = (n) => n + (n > 1 ? " candidatures" : " candidature");

	/* Écrit chaque valeur sur sa marque : le chiffre se lit sans survoler. */
	const valeurs = {
		id: "valeurs",
		afterDatasetsDraw(c, _a, o) {
			if (!o.actif) return;
			const { ctx } = c;
			ctx.save();
			ctx.font = '600 12px "Source Sans 3", sans-serif';
			ctx.fillStyle = ENCRE;
			if (o.actif === "point") {
				ctx.textAlign = "center";
				c.getDatasetMeta(0).data.forEach((p, i) => {
					const v = c.data.datasets[0].data[i];
					if (v) ctx.fillText(v, p.x, p.y - 9);
				});
			} else {
				ctx.textBaseline = "middle";
				const dernier = c.getDatasetMeta(c.data.datasets.length - 1).data;
				dernier.forEach((b, i) => {
					const t = c.data.datasets.reduce((s, d) => s + d.data[i], 0);
					ctx.fillText(t, b.x + 7, b.y);
				});
			}
			ctx.restore();
		},
	};
	Chart.register(valeurs);

	/* Total au centre de l'anneau. */
	const centre = {
		id: "centre",
		afterDraw(c, _a, o) {
			if (o.texte === undefined) return;
			const { ctx, chartArea: z } = c;
			ctx.save();
			ctx.textAlign = "center";
			ctx.fillStyle = ENCRE;
			ctx.font = '700 26px "Source Serif 4", serif';
			ctx.fillText(o.texte, (z.left + z.right) / 2, (z.top + z.bottom) / 2 + 4);
			ctx.fillStyle = GRIS;
			ctx.font = '13px "Source Sans 3", sans-serif';
			ctx.fillText("au total", (z.left + z.right) / 2, (z.top + z.bottom) / 2 + 22);
			ctx.restore();
		},
	};
	Chart.register(centre);

	/* Candidatures par jour */
	const toile = document.getElementById("g-jours");
	const degrade = toile.getContext("2d").createLinearGradient(0, 0, 0, 300);
	degrade.addColorStop(0, "rgba(0, 122, 61, .26)");
	degrade.addColorStop(1, "rgba(0, 122, 61, 0)");
	const total = document.querySelector("[data-jours-total]");
	const jours = new Chart(toile, {
		type: "line",
		data: { labels: [], datasets: [{ data: [], borderColor: VERT, backgroundColor: degrade, borderWidth: 2.5, fill: true, cubicInterpolationMode: "monotone", pointRadius: 3.5, pointBackgroundColor: "#fff", pointBorderColor: VERT, pointBorderWidth: 2, pointHoverRadius: 6, pointHitRadius: 16 }] },
		options: {
			responsive: true, maintainAspectRatio: false,
			layout: { padding: { top: 18 } },
			interaction: { mode: "index", intersect: false },
			plugins: {
				valeurs: { actif: "point" },
				tooltip: { callbacks: { title: (i) => long.format(date(i[0].label)), label: (i) => accord(i.parsed.y) } },
			},
			scales: {
				x: { grid: { display: false }, border: { color: "#adbbb1" }, ticks: { maxRotation: 0, autoSkipPadding: 18, callback(v) { return fr.format(date(this.getLabelForValue(v))); } } },
				y: { beginAtZero: true, grace: "12%", grid: { color: FILET }, border: { display: false }, ticks: { precision: 0, maxTicksLimit: 6 } },
			},
		},
	});
	const periode = (n) => {
		const s = D.jours.slice(-n);
		jours.data.labels = s.map((x) => x.d);
		jours.data.datasets[0].data = s.map((x) => x.n);
		jours.update();
		const t = s.reduce((a, x) => a + x.n, 0);
		total.textContent = accord(t) + " sur " + n + " jours, " + (t / n).toFixed(1).replace(".", ",") + " par jour en moyenne";
		document.querySelectorAll("[data-periode]").forEach((b) => b.setAttribute("aria-pressed", String(+b.dataset.periode === n)));
	};
	document.querySelectorAll("[data-periode]").forEach((b) => b.addEventListener("click", () => periode(+b.dataset.periode)));
	periode(30);

	const anneau = (id, noms, data, couleurs) => new Chart(document.getElementById(id), {
		type: "doughnut",
		data: { labels: noms, datasets: [{ data, backgroundColor: couleurs, borderColor: "#fff", borderWidth: 3, hoverOffset: 4 }] },
		options: { responsive: true, maintainAspectRatio: false, cutout: "72%", plugins: {
			centre: { texte: data.reduce((a, b) => a + b, 0) },
			tooltip: { callbacks: { label: (i) => i.label + " : " + i.parsed } },
		} },
	});
	anneau("g-sexes", ["Filles", "Garçons"], [D.sexes.f, D.sexes.g], [TILLEUL, VERT]);
	anneau("g-cycles", D.cycles.map((c) => c.nom), D.cycles.map((c) => c.n), [NUIT, TILLEUL]);

	const barres = (id, labels, datasets, empile) => new Chart(document.getElementById(id), {
		type: "bar",
		data: { labels, datasets },
		options: {
			indexAxis: "y", responsive: true, maintainAspectRatio: false,
			layout: { padding: { right: 30 } },
			plugins: { valeurs: { actif: "barre" }, tooltip: { displayColors: empile, callbacks: { label: (i) => (empile ? i.dataset.label + " : " : "") + i.parsed.x } } },
			scales: {
				x: { stacked: empile, display: false, beginAtZero: true },
				y: { stacked: empile, grid: { display: false }, border: { display: false }, ticks: { color: ENCRE, autoSkip: false, callback(v) { const t = this.getLabelForValue(v); return t.length > 34 ? t.slice(0, 32) + "…" : t; } } },
			},
		},
	});
	barres("g-centres", D.centres.noms, [{ data: D.centres.n, backgroundColor: VERT, borderRadius: 4, barThickness: 16 }], false);
	barres("g-filieres", D.filieres.map((f) => f.nom), [
		{ label: "Filles", data: D.filieres.map((f) => f.f), backgroundColor: TILLEUL, barThickness: 18, borderColor: "#fff", borderWidth: { right: 2 } },
		{ label: "Garçons", data: D.filieres.map((f) => f.g), backgroundColor: VERT, barThickness: 18, borderRadius: { topRight: 4, bottomRight: 4 } },
	], true);
})();
