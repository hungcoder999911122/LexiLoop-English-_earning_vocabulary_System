package com.example.englishvocab.controller;

import jakarta.persistence.EntityManager;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/admin/reports")
@PreAuthorize("hasRole('ADMIN')")
public class AdminReportController {
    private final EntityManager em;

    public AdminReportController(EntityManager em) {
        this.em = em;
    }

    @GetMapping("/topics")
    public List<?> topics() {
        return em.createNativeQuery("SELECT * FROM v_topic_report").getResultList();
    }

    @GetMapping("/leaderboard")
    public List<?> leaderboard() {
        return em.createNativeQuery("SELECT * FROM v_leaderboard").getResultList();
    }

    @GetMapping("/audit")
    public List<?> audit() {
        return em.createNativeQuery("SELECT * FROM Audit_Logs ORDER BY created_at DESC LIMIT 50").getResultList();
    }

    @GetMapping("/summary")
    public Map<String, Object> summary() {
        Number users = (Number) em.createNativeQuery("SELECT COUNT(*) FROM Users").getSingleResult();
        Number vocabs = (Number) em.createNativeQuery("SELECT COUNT(*) FROM Vocabulary").getSingleResult();
        Number reviews = (Number) em.createNativeQuery("SELECT COUNT(*) FROM Review_Logs").getSingleResult();
        Number quizzes = (Number) em.createNativeQuery("SELECT COUNT(*) FROM Quiz_Results").getSingleResult();
        return Map.of("users", users, "vocabularies", vocabs, "reviews", reviews, "quizResults", quizzes);
    }
}
