import Alpine from "alpinejs";

Alpine.data("IceComponent", (c) => {
    const data = JSON.parse(c.dataset.props);
    return {
        ...data,
        init() {
            // Later
        },
    };
});

Alpine.magic("ice", (el, { Alpine }) => {
    return {
        call(method = "", ...args) {
            const [mostData] = Alpine.closestDataStack(el);
            const root = Alpine.closestRoot(el);
            const data = Object.fromEntries(Object.entries(mostData));
            const snapshot = root.dataset.props;

            const res = fetch("/__icecube__", {
                method: "POST",
                headers: {
                    "X-Ice": root.getAttribute("x-ice"),
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    id: root.id,
                    component: root.getAttribute("data-icecube"),
                    method,
                    args,
                    data,
                    snapshot,
                }),
            });

            res.then((res) => {
                if (res.ok) {
                    res.json().then(({ data, html }) => {
                        if (html) {
                            root.outerHTML = html;
                            return;
                        }

                        const ds = root._x_dataStack[0];
                        Object.entries(data).forEach(([key, value]) => {
                            ds[key] = value;
                        });
                    });
                }
            });
        },
    };
});

window.Alpine = Alpine;
Alpine.start();
