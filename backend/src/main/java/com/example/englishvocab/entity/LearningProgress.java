package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;

@Getter
@Setter
@Entity
@Table(name = "User_Vocab_Progress")
public class LearningProgress {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer progressID;

    @ManyToOne(optional = false)
    @JoinColumn(name = "userID")
    private User user;

    @ManyToOne(optional = false)
    @JoinColumn(name = "vocabularyID")
    private Vocabulary vocabulary;

    @Column(name = "ease_factor", precision = 4, scale = 2)
    private BigDecimal easeFactor;

    @Column(name = "interval_days")
    private Integer intervalDays;

    private Integer repetitions;

    private Integer lapses;

    @Column(name = "mastery_level")
    private String masteryLevel;

    @Column(name = "next_review_date")
    private LocalDate nextReviewDate;

    @Column(name = "last_reviewed_at")
    private LocalDateTime lastReviewedAt;

    @Column(name = "last_quality_rating")
    private Integer lastQualityRating;
}
