import { defineType, defineField } from "sanity";

export default defineType({
  name: "project",
  title: "Referenz / Projekt",
  type: "document",
  fields: [
    defineField({
      name: "title",
      title: "Titel",
      type: "string",
      validation: (r) => r.required(),
    }),
    defineField({
      name: "slug",
      title: "URL-Kürzel",
      type: "slug",
      options: { source: "title", maxLength: 96 },
      validation: (r) => r.required(),
    }),
    defineField({
      name: "client",
      title: "Kunde",
      type: "string",
    }),
    defineField({
      name: "categories",
      title: "Kategorien",
      type: "array",
      of: [{ type: "string" }],
      options: {
        list: [
          { title: "Grafikdesign", value: "grafik" },
          { title: "Webdesign", value: "web" },
          { title: "Videoproduktion", value: "video" },
        ],
        layout: "tags",
      },
      validation: (r) => r.required().min(1),
    }),
    defineField({
      name: "coverImage",
      title: "Titelbild",
      type: "image",
      options: { hotspot: true },
      fields: [{ name: "alt", title: "Alt-Text", type: "string" }],
      validation: (r) => r.required(),
    }),
    defineField({
      name: "gallery",
      title: "Bildergalerie",
      type: "array",
      of: [
        {
          type: "image",
          options: { hotspot: true },
          fields: [{ name: "alt", title: "Alt-Text", type: "string" }],
        },
      ],
    }),
    defineField({
      name: "excerpt",
      title: "Kurzbeschreibung (für Übersicht)",
      type: "text",
      rows: 3,
    }),
    defineField({
      name: "body",
      title: "Projektbeschreibung",
      type: "array",
      of: [{ type: "block" }],
    }),
    defineField({
      name: "publishedAt",
      title: "Datum",
      type: "datetime",
      initialValue: () => new Date().toISOString(),
    }),
    defineField({
      name: "featured",
      title: "Auf Startseite hervorheben",
      type: "boolean",
      initialValue: false,
    }),
    defineField({
      name: "order",
      title: "Sortierung (kleiner = weiter vorne)",
      type: "number",
    }),
  ],
  orderings: [
    {
      title: "Sortierung",
      name: "orderAsc",
      by: [{ field: "order", direction: "asc" }],
    },
  ],
  preview: {
    select: { title: "title", subtitle: "client", media: "coverImage" },
  },
});
