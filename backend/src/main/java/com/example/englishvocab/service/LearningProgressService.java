package com.example.englishvocab.service;

import com.example.englishvocab.dto.ApiDtos.ReviewRequest;
import com.example.englishvocab.dto.ApiDtos.ReviewResponse;
import com.example.englishvocab.dto.ApiDtos.VocabDto;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.repository.VocabularyRepository;
import jakarta.persistence.EntityManager;
import jakarta.persistence.ParameterMode;
import jakarta.persistence.StoredProcedureQuery;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.sql.Date;
import java.time.LocalDate;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Service
public class LearningProgressService {
    private final EntityManager entityManager;
    private final VocabularyRepository vocabularyRepository;
    private final VocabularyService vocabularyService;

    public LearningProgressService(EntityManager entityManager, VocabularyRepository vocabularyRepository,
                                   VocabularyService vocabularyService) {
        this.entityManager = entityManager;
        this.vocabularyRepository = vocabularyRepository;
        this.vocabularyService = vocabularyService;
    }

    @Transactional
    public void enroll(User user, Integer topicId) {
        StoredProcedureQuery q = entityManager.createStoredProcedureQuery("sp_enroll_topic");
        q.registerStoredProcedureParameter("p_userID", Integer.class, ParameterMode.IN);
        q.registerStoredProcedureParameter("p_topicID", Integer.class, ParameterMode.IN);
        q.setParameter("p_userID", user.getUserID());
        q.setParameter("p_topicID", topicId);
        q.execute();
    }

    @SuppressWarnings("unchecked")
    public List<VocabDto> dueCards(User user) {
        List<?> rows = entityManager.createNativeQuery("""
                SELECT vocabularyID FROM v_due_reviews
                WHERE userID = :uid
                ORDER BY next_review_date ASC
                """).setParameter("uid", user.getUserID()).getResultList();
        return rows.stream()
                .map(r -> ((Number) (r instanceof Object[] arr ? arr[0] : r)).intValue())
                .map(id -> vocabularyService.toDto(
                        vocabularyRepository.findById(id).orElseThrow(), user.getUserID()))
                .toList();
    }

    @Transactional
    public ReviewResponse review(User user, ReviewRequest req) {
        if (req.quality() == null || req.quality() < 0 || req.quality() > 5) {
            throw new IllegalArgumentException("quality phải từ 0 đến 5 (SM-2)");
        }
        StoredProcedureQuery q = entityManager.createStoredProcedureQuery("sp_review_vocabulary");
        q.registerStoredProcedureParameter("p_userID", Integer.class, ParameterMode.IN);
        q.registerStoredProcedureParameter("p_vocabularyID", Integer.class, ParameterMode.IN);
        q.registerStoredProcedureParameter("p_quality", Integer.class, ParameterMode.IN);
        q.registerStoredProcedureParameter("p_response_ms", Integer.class, ParameterMode.IN);
        q.registerStoredProcedureParameter("p_next_date", Date.class, ParameterMode.OUT);
        q.registerStoredProcedureParameter("p_mastery", String.class, ParameterMode.OUT);
        q.registerStoredProcedureParameter("p_interval", Integer.class, ParameterMode.OUT);
        q.setParameter("p_userID", user.getUserID());
        q.setParameter("p_vocabularyID", req.vocabularyId());
        q.setParameter("p_quality", req.quality());
        q.setParameter("p_response_ms", req.responseTimeMs());
        q.execute();
        Date next = (Date) q.getOutputParameterValue("p_next_date");
        String mastery = (String) q.getOutputParameterValue("p_mastery");
        Integer interval = (Integer) q.getOutputParameterValue("p_interval");
        LocalDate nextDate = next == null ? null : next.toLocalDate();
        return new ReviewResponse(nextDate, mastery, interval);
    }

    public Map<String, Object> dashboard(User user) {
        Map<String, Object> out = new HashMap<>();
        out.put("stats", entityManager.createNativeQuery(
                        "SELECT * FROM v_user_learning_stats WHERE userID = :id")
                .setParameter("id", user.getUserID()).getResultList());
        out.put("due", entityManager.createNativeQuery(
                        "SELECT vocabularyID, word, topicName, mastery_level, next_review_date FROM v_due_reviews WHERE userID = :id ORDER BY next_review_date")
                .setParameter("id", user.getUserID()).getResultList());
        out.put("recentQuizzes", entityManager.createNativeQuery("""
                        SELECT qr.resultID, t.topicName, qr.score, qr.total,
                               ROUND(qr.score / qr.total * 100, 1) AS percent, qr.taken_at
                        FROM Quiz_Results qr
                        JOIN Quizzes q ON q.quizID = qr.quizID
                        JOIN Topics t ON t.topicID = q.topicID
                        WHERE qr.userID = :id
                        ORDER BY qr.taken_at DESC
                        LIMIT 5
                        """).setParameter("id", user.getUserID()).getResultList());
        out.put("weak", entityManager.createNativeQuery(
                        "SELECT vocabularyID, word, topicName, avg_quality, times_reviewed FROM v_weak_words WHERE userID = :id")
                .setParameter("id", user.getUserID()).getResultList());
        return out;
    }
}
