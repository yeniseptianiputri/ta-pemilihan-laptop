"use client";

import { ReactNode } from "react";

interface BadgeProps {
  children: ReactNode;
  tone?: "default" | "warning";
}

const toneClasses: Record<NonNullable<BadgeProps["tone"]>, string> = {
  default: "bg-blue-50 text-blue-700 border-blue-200",
  warning: "bg-amber-50 text-amber-700 border-amber-200",
};

export const Badge = ({ children, tone = "default" }: BadgeProps) => (
  <span
    className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium ${toneClasses[tone]}`}
  >
    {children}
  </span>
);

export default Badge;
