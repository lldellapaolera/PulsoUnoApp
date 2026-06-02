# 🚀 Git & GitHub: Kit de Supervivencia

[cite_start]Una guía rápida y práctica con los comandos más frecuentes de Git y la solución al error de carpetas dudosas (`dubious ownership`)[cite: 2, 3]. [cite_start]Ideal para tener a mano durante el desarrollo diario[cite: 5, 7].

---

## 🛡️ Cómo marcar una carpeta como segura

[cite_start]Si te aparece el error `fatal: detected dubious ownership in repository`, Git está bloqueando la carpeta por seguridad[cite: 3, 4]. [cite_start]Para solucionarlo y marcarla como segura, ejecuta uno de los siguientes comandos en tu terminal:

### Opción 1: Para una carpeta específica
```bash
git config --global --add safe.directory "RUTA_DE_TU_CARPETA"# PulsoUnoApp
